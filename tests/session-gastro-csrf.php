<?php
// Isolated harness. Never load protected/config.php or application databases.
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$socket=getenv('GASTRO_QA_SOCKET')?:'';
if(!str_starts_with($socket,'/tmp/simion-gastro-db-'))throw new RuntimeException('Isolated socket required');
foreach(['GDO_DOMAIN'=>'isolated.invalid','GDO_GDT_DEBUG'=>0,'GDO_DB_READONLY'=>false,'GDO_IPC'=>'none','GDO_TIME_START'=>microtime(true),'GDO_MEMCACHE'=>false,'GDO_DB_DEBUG'=>0] as $k=>$v)define($k,$v);
$core=getenv('GASTRO_QA_CORE')?:'';
if(!is_file($core.'/GDO7.php'))throw new RuntimeException('Explicit GASTRO_QA_CORE required');
require $core.'/GDO7.php';
\GDO\Core\Application::$INSTANCE=\GDO\Core\Application::make()->cli(true);
use GDO\DB\Database;
use GDO\DBMS\Module_DBMS;
use GDO\Session\GDO_Session;
$c=new mysqli('localhost',getenv('USER'),'','',0,$socket);
$worker=($argv[1]??'')==='logout-worker';
$schema=$worker?($argv[2]??''):'simion_session_qa_'.bin2hex(random_bytes(6));
if(!preg_match('/^simion_session_qa_[a-f0-9]{12}$/D',$schema))throw new RuntimeException('Invalid QA schema');
if(!$worker)$c->query('CREATE DATABASE `'.$schema.'`');
$c->select_db($schema);
$db=new Database('localhost','','',$schema,0);$db->link=$c;
$rc=new ReflectionClass(Module_DBMS::class);$dbms=$rc->newInstanceWithoutConstructor();
$rc->getProperty('link')->setValue($dbms,$c);
(new ReflectionClass(Database::class))->getProperty('DBMS')->setValue(null,$dbms);
if($worker){
 $row=$c->query('SELECT * FROM gdo_session WHERE sess_id=1')->fetch_assoc();
 $logout=GDO_Session::blank($row)->setPersisted(true)->dirty(false);
 // Exact session mutations used by Logout::execute, with real GDO save/hooks.
 // The HTTP method and user/logout application hooks are not exercised here.
 $logout->authenticate(null);
 echo "LOGOUT_SAVED\n";exit;
}
$c->query('CREATE TABLE gdo_session(sess_id BIGINT PRIMARY KEY,sess_user BIGINT NULL,sess_token VARCHAR(64),sess_data TEXT,sess_time DATETIME,sess_created DATETIME,sess_ip VARCHAR(45),sess_auth CHAR(32) NOT NULL) ENGINE=InnoDB');
$c->query("INSERT INTO gdo_session VALUES(1,1,'synthetic-only',NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),NULL,'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')");
require_once __DIR__.'/../Commerce/TransactionStore.php';
use GDO\LinkUUp\Commerce\TransactionStore;
$c->query("CREATE TABLE users(user_id INT PRIMARY KEY,user_type VARCHAR(16),user_deleted DATETIME NULL) ENGINE=InnoDB");
$c->query("INSERT INTO users VALUES(1,'member',NULL)");
$c->query('CREATE TABLE rooms(room_id INT PRIMARY KEY) ENGINE=InnoDB');$c->query('INSERT INTO rooms VALUES(1)');
$c->query('CREATE TABLE store(store_id INT PRIMARY KEY,store_data MEDIUMTEXT) ENGINE=InnoDB');
$c->query("INSERT INTO store VALUES(1,'{\"rooms\":{},\"ledger\":{}}')");
$tables=['session'=>'gdo_session','user'=>'users','room'=>'rooms','store'=>'store'];
$id=['session'=>'1','user'=>'1','room'=>'1','token'=>'synthetic-only','allowCsrfInit'=>true,'requireCsrf'=>false];
$row=$c->query('SELECT * FROM gdo_session WHERE sess_id=1')->fetch_assoc();
GDO_Session::$INSTANCE=GDO_Session::blank($row)->setPersisted(true)->dirty(false);
GDO_Session::set('qa_marker','stale');
$read=static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']];
$first=TransactionStore::run($c,$tables,$id,false,$read);
if($c->query('SELECT sess_data FROM gdo_session')->fetch_row()[0]!==null)throw new RuntimeException('Store wrote GDO session');
if(!GDO_Session::commit())throw new RuntimeException('GDO commit failed');
$after=TransactionStore::run($c,$tables,$id,false,$read);
if($first['csrf']!==$after['csrf'])throw new RuntimeException('Stale GDO commit changed CSRF');
$valid=$id;$valid['allowCsrfInit']=false;$valid['requireCsrf']=true;$valid['csrf']=$first['csrf'];
TransactionStore::run($c,$tables,$valid,false,$read);
$proc=proc_open([PHP_BINARY,__FILE__,'logout-worker',$schema],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
if(!is_resource($proc))throw new RuntimeException('Worker unavailable');
fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
if(proc_close($proc)!==0||trim($out)!=='LOGOUT_SAVED'||$err!=='')throw new RuntimeException('Worker failed');
try{TransactionStore::run($c,$tables,$valid,false,$read);throw new LogicException('Revoked session accepted');}catch(RuntimeException $e){if($e->getCode()!==403)throw $e;}
// Reauthentication of the SAME user with unchanged cookie must get a new CSRF binding.
$row=$c->query('SELECT * FROM gdo_session WHERE sess_id=1')->fetch_assoc();
$guest=GDO_Session::blank($row)->setPersisted(true)->dirty(false);
$c->query('CREATE TABLE gdo_user LIKE users');$c->query('INSERT INTO gdo_user SELECT * FROM users');
$oldGeneration=$guest->getAuthGeneration();
$guest->authenticate('1');
if($guest->getAuthGeneration()===$oldGeneration)throw new RuntimeException('Authentication generation unchanged');
try{TransactionStore::run($c,$tables,$valid,false,$read);throw new LogicException('Old CSRF accepted after reauthentication');}catch(RuntimeException $e){if($e->getCode()!==403)throw $e;}
$second=TransactionStore::run($c,$tables,$id,false,$read);
if($second['csrf']===$first['csrf'])throw new RuntimeException('CSRF reused after reauthentication');
echo json_encode(['schema'=>$schema,'gastro_csrf_regression_pass'=>true,'store_does_not_write_session'=>true,'csrf_survives_dirty_gdo_commit'=>true,'revoked_session_denied'=>true,'same_user_reauthentication_rotates_csrf'=>true,'scope'=>'Real GDO Session and TransactionStore, isolated DBMS link and synthetic tables; no HTTP/WS or full application proof. Includes actual authenticate() transitions; not the full Login Form/HTTP/WS hooks.']),PHP_EOL;
