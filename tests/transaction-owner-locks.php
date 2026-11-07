<?php
declare(strict_types=1);
namespace GDO\DB {final class Database{public static $instance;public $link;public static function instance(){return self::$instance;}public function getLink(){return $this->link;}}}
namespace {
if(PHP_SAPI!=='cli')exit(1);
$socket=getenv('GASTRO_QA_SOCKET')?:'';
if(!str_starts_with($socket,'/tmp/simion-gastro-db-'))throw new RuntimeException('Explicit isolated socket required');
require dirname(__DIR__).'/Commerce/TransactionStore.php';require dirname(__DIR__).'/Commerce/OperatorApplications.php';
use GDO\LinkUUp\Commerce\{TransactionStore,OperatorApplications};
$db=new mysqli('localhost',getenv('GASTRO_QA_DB_USER')?:getenv('USER'),'','',0,$socket);
$db->query('SET innodb_lock_wait_timeout=8');
$worker=($argv[1]??'')==='worker';$schema=$worker?($argv[2]??''):'simion_ownerlock_qa_'.bin2hex(random_bytes(6));
if(!preg_match('/^simion_ownerlock_qa_[a-f0-9]{12}$/D',$schema))throw new RuntimeException('Invalid schema');
if(!$worker)$db->query('CREATE DATABASE `'.$schema.'`');$db->select_db($schema);
$bridge=new \GDO\DB\Database();$bridge->link=$db;\GDO\DB\Database::$instance=$bridge;
$tables=['session'=>'gdo_session','user'=>'gdo_user','room'=>'lup_room','store'=>'lup_gastro_store'];
$identity=['session'=>'1','token'=>'synthetic-token','user'=>'2','room'=>'10','allowCsrfInit'=>true];
if($worker){
 echo $db->thread_id."\n";flush();
 try{$r=TransactionStore::run($db,$tables,$identity,false,function(array &$store,array $a):array{return ['approved'=>OperatorApplications::approvalFor((string)$a['room']['room_owner'],'10')];});echo $r['approved']?"APPROVED\n":"DENIED\n";}
 catch(RuntimeException $e){echo 'ERROR '.$e->getCode()."\n";}exit;
}
$n=0;function check(bool $yes):void{global $n;if(!$yes)throw new RuntimeException('Check failed '.($n+1));++$n;}
function startWorker():array{global $schema;$p=proc_open([PHP_BINARY,__FILE__,'worker',$schema],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))throw new RuntimeException('No worker');fclose($pipes[0]);$id=(int)trim(fgets($pipes[1]));return[$p,$pipes,$id];}
function waiting(int $id):void{global $db;for($i=0;$i<100;$i++){$r=$db->query("SELECT INFO FROM information_schema.PROCESSLIST WHERE ID=$id")->fetch_assoc();if(str_contains($r['INFO']??'','gdo_user')&&str_contains($r['INFO'],'FOR UPDATE')){usleep(50000);$r=$db->query("SELECT INFO FROM information_schema.PROCESSLIST WHERE ID=$id")->fetch_assoc();if(str_contains($r['INFO']??'','gdo_user'))return;}usleep(20000);}throw new RuntimeException('Worker did not reach user row lock');}
function finish(array $w,string $expected):void{[$p,$pipes]=$w;$out=trim(stream_get_contents($pipes[1]));$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($p)===0&&$err===''&&$out===$expected);}
try{
$db->query('CREATE TABLE gdo_user(user_id BIGINT PRIMARY KEY,user_type VARCHAR(20),user_deleted DATETIME NULL) ENGINE=InnoDB');$db->query("INSERT INTO gdo_user VALUES(1,'member',NULL),(2,'member',NULL),(3,'member',NULL)");
$db->query('CREATE TABLE lup_room(room_id BIGINT PRIMARY KEY,room_owner BIGINT,room_deleted DATETIME NULL) ENGINE=InnoDB');$db->query('INSERT INTO lup_room VALUES(10,1,NULL)');
$db->query('CREATE TABLE gdo_session(sess_id BIGINT PRIMARY KEY,sess_user BIGINT,sess_token VARCHAR(64),sess_auth CHAR(32),sess_time DATETIME) ENGINE=InnoDB');$db->query("INSERT INTO gdo_session VALUES(1,2,'synthetic-token','aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',UTC_TIMESTAMP())");
$db->query('CREATE TABLE lup_gastro_store(store_id INT PRIMARY KEY,store_data MEDIUMTEXT) ENGINE=InnoDB');$db->query("INSERT INTO lup_gastro_store VALUES(1,'{\"rooms\":{},\"ledger\":{}}')");
$db->query('CREATE TABLE lup_operatorapplication(oa_id BIGINT PRIMARY KEY,oa_user BIGINT,oa_room BIGINT,oa_status VARCHAR(20)) ENGINE=InnoDB');$db->query("INSERT INTO lup_operatorapplication VALUES(1,1,10,'approved')");
// Existing owner request / admin-review prefix holds the owner before room.
$db->begin_transaction();$db->query('SELECT * FROM gdo_user WHERE user_id=1 FOR UPDATE');
$w=startWorker();try{waiting($w[2]);}catch(RuntimeException $e){$db->rollback();finish($w,'APPROVED');throw $e;}
// With the old code the guest already held room and this creates a deadlock.
$db->query('SELECT * FROM lup_room WHERE room_id=10 FOR UPDATE');check(true);$db->commit();finish($w,'APPROVED');
// Ownership changes after the hint: do not use an unlocked new owner's approval.
$db->begin_transaction();$db->query('SELECT * FROM gdo_user WHERE user_id=1 FOR UPDATE');
$w=startWorker();waiting($w[2]);$db->query('UPDATE lup_room SET room_owner=3 WHERE room_id=10');$db->commit();finish($w,'ERROR 409');
// A subsequent request observes the new owner; no old approval remains valid.
$r=TransactionStore::run($db,$tables,$identity,false,function(array &$s,array $a):array{return ['approved'=>OperatorApplications::approvalFor((string)$a['room']['room_owner'],'10')];});check($r['approved']===false);
echo "$n owner-lock ordering/race checks passed with two real connections and production TransactionStore/approvalFor.\n";
}finally{if((int)$db->query('SELECT @@in_transaction')->fetch_row()[0])$db->rollback();$db->query('DROP DATABASE `'.$schema.'`');}
}
