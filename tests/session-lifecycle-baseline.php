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
 $logout->setVar('sess_user',null);$logout->setVar('sess_data',null);$logout->save();
 echo "LOGOUT_SAVED\n";exit;
}
$c->query('CREATE TABLE gdo_session(sess_id BIGINT PRIMARY KEY,sess_user BIGINT NULL,sess_token VARCHAR(64),sess_data TEXT,sess_time DATETIME,sess_created DATETIME,sess_ip VARCHAR(45)) ENGINE=InnoDB');
$c->query("INSERT INTO gdo_session VALUES(1,1,'synthetic-only',NULL,UTC_TIMESTAMP(),UTC_TIMESTAMP(),NULL)");
$results=[];
foreach([false,true] as $dirty){
 $c->query('UPDATE gdo_session SET sess_user=1,sess_data=NULL WHERE sess_id=1');
 $row=$c->query('SELECT * FROM gdo_session WHERE sess_id=1')->fetch_assoc();
 $s=GDO_Session::blank($row)->setPersisted(true)->dirty(false);
 GDO_Session::$INSTANCE=$s;
 if($dirty)GDO_Session::set('qa_marker','stale');
 $proc=proc_open([PHP_BINARY,__FILE__,'logout-worker',$schema],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if(!is_resource($proc))throw new RuntimeException('Worker unavailable');
 fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
 if(proc_close($proc)!==0 || trim($out)!=='LOGOUT_SAVED' || $err!=='')throw new RuntimeException('Worker failed: '.$err);
 $loggedOut=$c->query('SELECT sess_user,sess_data FROM gdo_session WHERE sess_id=1')->fetch_assoc();
 if($loggedOut['sess_user']!==null || $loggedOut['sess_data']!==null)throw new RuntimeException('Logout not persisted');
 if(!GDO_Session::commit())throw new RuntimeException('Commit failed');
 $r=$c->query('SELECT sess_user,sess_data FROM gdo_session WHERE sess_id=1')->fetch_assoc();
 $restored=$r['sess_data']!==null;
 if($r['sess_user']!==null || $restored!==$dirty)throw new RuntimeException('Unexpected baseline behavior');
 $results[]=['dirty_before_logout'=>$dirty,'logout_persisted'=>true,'user_stays_null'=>$r['sess_user']===null,'data_restored'=>$restored];
}
echo json_encode(['schema'=>$schema,'results'=>$results,'security_pass'=>false,'scope'=>'Real GDO session set/save/commit with separate logout process; synthetic InnoDB table, injected isolated DBMS connection, no HTTP/WS/login hooks. Baseline demonstrates stale data resurrection, not login bypass.']),PHP_EOL;
