<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with(getenv('GASTRO_QA_SOCKET')?:'', '/tmp/simion-gastro-db-'))exit('Explicit isolated QA socket required');
require_once __DIR__.'/../Commerce/TransactionStore.php';
require_once __DIR__.'/../Commerce/TestWallet.php';
use GDO\LinkUUp\Commerce\TransactionStore;
use GDO\LinkUUp\Commerce\TestWallet;
$c=new mysqli('localhost',getenv('USER'),'','',0,getenv('GASTRO_QA_SOCKET'));
$schema='simion_gastro_qa_'.bin2hex(random_bytes(6));
$c->query('CREATE DATABASE `'.$schema.'`');$c->select_db($schema);
$tables=['session'=>'sessions','user'=>'users','room'=>'rooms','store'=>'store'];
$c->query('CREATE TABLE sessions(sess_id INT PRIMARY KEY,sess_user INT NULL,sess_token VARCHAR(64),sess_data TEXT,sess_time DATETIME,sess_auth CHAR(32) NOT NULL) ENGINE=InnoDB');
$c->query('CREATE TABLE users(user_id INT PRIMARY KEY,user_type VARCHAR(16),user_deleted DATETIME NULL) ENGINE=InnoDB');
$c->query('CREATE TABLE rooms(room_id INT PRIMARY KEY,room_owner INT,room_enabled BOOL,room_deleted DATETIME NULL,room_name VARCHAR(80)) ENGINE=InnoDB');
$c->query('CREATE TABLE store(store_id INT PRIMARY KEY,store_data MEDIUMTEXT NOT NULL) ENGINE=InnoDB');
$c->query("INSERT INTO users VALUES (1,'member',NULL),(2,'member',NULL)");
$c->query("INSERT INTO rooms VALUES(1,2,1,NULL,'QA only')");
$token=bin2hex(random_bytes(16));$csrf=bin2hex(random_bytes(32));$serialized=serialize(['gastro_local_csrf'=>$csrf]);
$stmt=$c->prepare("INSERT INTO sessions VALUES (1,1,?,?,UTC_TIMESTAMP(),'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')");$stmt->bind_param('ss',$token,$serialized);$stmt->execute();
$c->query("INSERT INTO store VALUES(1,'{\"rooms\":{},\"ledger\":{}}')");
$id=['session'=>'1','user'=>'1','room'=>'1','token'=>$token,'csrf'=>$csrf,'requireCsrf'=>true,'maxAge'=>86400];$checks=0;
function check(bool $v):void{global $checks;if(!$v)throw new RuntimeException('Assertion failed');$checks++;}
function denied(callable $f,int $code):void{try{$f();throw new LogicException('Unexpected success');}catch(RuntimeException $e){check($e->getCode()===$code);}}
$init=$id;$init['allowCsrfInit']=true;$init['requireCsrf']=false;
$initialized=TransactionStore::run($c,$tables,$init,false,static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']]);
$id['csrf']=$initialized['csrf'];
check(strlen($id['csrf'])===64);
check($c->query('SELECT sess_data FROM sessions WHERE sess_id=1')->fetch_row()[0]===$serialized);
$read=fn()=>json_decode($c->query('SELECT store_data FROM store')->fetch_row()[0],true);
$op=static function(array &$s,array $a):array{return TestWallet::topup($s['ledger'],'1',2000,'qa-topup');};
TransactionStore::run($c,$tables,$id,true,$op);
check(TestWallet::summary($read()['ledger'],'1')['availableCents']===2000);
TransactionStore::run($c,$tables,$id,true,$op);
check(TestWallet::summary($read()['ledger'],'1')['availableCents']===2000);
try{TransactionStore::run($c,$tables,$id,true,static function(array &$s):array{$s['ledger']=[];throw new RuntimeException('forced rollback');});}catch(RuntimeException $e){check($e->getMessage()==='forced rollback');}
check(TestWallet::summary($read()['ledger'],'1')['availableCents']===2000);
$c->query('UPDATE sessions SET sess_user=NULL WHERE sess_id=1');
denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),403);
$c->query('UPDATE sessions SET sess_user=1 WHERE sess_id=1');
$c->query('UPDATE users SET user_deleted=NOW() WHERE user_id=1');
denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),403);
$c->query('UPDATE users SET user_deleted=NULL WHERE user_id=1');
$bad=$id;$bad['token']='wrong';denied(fn()=>TransactionStore::run($c,$tables,$bad,true,$op),403);
$bad=$id;$bad['csrf']='wrong';denied(fn()=>TransactionStore::run($c,$tables,$bad,true,$op),403);
$c->query('UPDATE sessions SET sess_time=UTC_TIMESTAMP()-INTERVAL 2 DAY WHERE sess_id=1');denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),403);$c->query('UPDATE sessions SET sess_time=UTC_TIMESTAMP() WHERE sess_id=1');
$c->begin_transaction();denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),503);$c->rollback();
$c->query('ALTER TABLE sessions ENGINE=MyISAM');denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),503);$c->query('ALTER TABLE sessions ENGINE=InnoDB');
check(TestWallet::summary($read()['ledger'],'1')['availableCents']===2000);

function worker(string $mode):array {
 global $schema,$tables,$id;
 $p=proc_open([PHP_BINARY,__DIR__.'/transaction-worker.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if(!is_resource($p))throw new RuntimeException('Worker unavailable');
 fwrite($pipes[0],json_encode(['schema'=>$schema,'mode'=>$mode,'tables'=>$tables,'identity'=>$id]));fclose($pipes[0]);
 if(trim(fgets($pipes[1]))!=='READY')throw new RuntimeException('Worker did not start');
 return [$p,$pipes];
}
function finish(array $worker):void {
 [$p,$pipes]=$worker;stream_set_blocking($pipes[1],true);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
 check(proc_close($p)===0 && trim($out)==='DONE' && $err==='');
}
// Eight real independent connections must not credit an idempotency key twice.
$workers=[];for($i=0;$i<8;$i++)$workers[]=worker('topup');foreach($workers as $w)finish($w);
check(TestWallet::summary($read()['ledger'],'1')['availableCents']===2100);
// Revocation/deactivation must wait until the checked operation commits.
foreach(['logout','deactivate','room'] as $mode){
 $w=null;
 TransactionStore::run($c,$tables,$id,true,function(array &$s) use($mode,&$w):array {
  $w=worker($mode);stream_set_blocking($w[1][1],false);usleep(150000);
  check(stream_get_contents($w[1][1])==='');
  return TestWallet::topup($s['ledger'],'1',100,'before-'.$mode);
 });
 finish($w);
 if($mode==='logout'){denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),403);$c->query('UPDATE sessions SET sess_user=1 WHERE sess_id=1');}
 if($mode==='deactivate'){denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),403);$c->query('UPDATE users SET user_deleted=NULL WHERE user_id=1');}
 if($mode==='room'){
  denied(fn()=>TransactionStore::run($c,$tables,$id,true,static function(array &$s,array $a):array{if(!$a['room']['room_enabled'])throw new RuntimeException('disabled',409);return [];}),409);
  $c->query('UPDATE rooms SET room_enabled=1 WHERE room_id=1');
 }
}
check(TestWallet::summary($read()['ledger'],'1')['availableCents']===2400);
// A stale GDO sess_data write must not replace or invalidate the independent CSRF.
$c->query('UPDATE sessions SET sess_data=NULL WHERE sess_id=1');
$init=$id;$init['allowCsrfInit']=true;$init['requireCsrf']=false;
$result=TransactionStore::run($c,$tables,$init,false,static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']]);
check($result['csrf']===$id['csrf']);
check($c->query('SELECT sess_data FROM sessions WHERE sess_id=1')->fetch_row()[0]===null);
$old=$id;$old['csrf']=$csrf;denied(fn()=>TransactionStore::run($c,$tables,$old,true,$op),403);
// Rotating the real session token cannot reuse an old CSRF token.
$newToken=bin2hex(random_bytes(16));$q=$c->prepare('UPDATE sessions SET sess_token=? WHERE sess_id=1');$q->bind_param('s',$newToken);$q->execute();
$rotated=$id;$rotated['token']=$newToken;
denied(fn()=>TransactionStore::run($c,$tables,$rotated,true,$op),403);
$rotated['allowCsrfInit']=true;$rotated['requireCsrf']=false;
$new=TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']]);
check($new['csrf']!==$id['csrf']);
denied(fn()=>TransactionStore::run($c,$tables,$id,true,$op),403);
// Missing auth generation must not silently fall back to pre-login CSRF binding.
$c->query("UPDATE sessions SET sess_auth='' WHERE sess_id=1");
denied(fn()=>TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>[]),503);
$c->query("UPDATE sessions SET sess_auth='aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa' WHERE sess_id=1");
// Expiry and capacity use deterministic fixture edits, no wall-clock sleeps.
$persist=static function(array $s) use($c):void {$j=json_encode($s,JSON_THROW_ON_ERROR);$q=$c->prepare('UPDATE store SET store_data=? WHERE store_id=1');$q->bind_param('s',$j);$q->execute();};
$key=hash('sha256',json_encode([$rotated['session'],$rotated['user'],$rotated['token'],'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa'],JSON_THROW_ON_ERROR));
$state=$read();$expires=$state['csrf'][$key]['expires'];
TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>[]);
check($read()['csrf'][$key]['expires']===$expires);
$state=$read();$state['csrf'][$key]['expires']=time()-1;$persist($state);
$expired=$rotated;$expired['allowCsrfInit']=false;$expired['requireCsrf']=true;$expired['csrf']=$new['csrf'];
denied(fn()=>TransactionStore::run($c,$tables,$expired,false,static fn(array &$s,array $a):array=>[]),403);
$renewed=TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']]);
check($renewed['csrf']!==$new['csrf']);
$state=$read();$state['csrf']=[];
for($i=0;$i<512;$i++)$state['csrf'][hash('sha256','fixture-'.$i)]=['token'=>str_repeat('a',64),'expires'=>time()+1800];
$persist($state);
denied(fn()=>TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>[]),503);
check(count($read()['csrf'])===512);
$state=$read();foreach($state['csrf'] as &$entry)$entry['expires']=time()-1;unset($entry);$persist($state);
$clean=TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']]);
check(count($read()['csrf'])===1);
check(strlen($clean['csrf'])===64);
// Legacy string entries are invalidated on a successful state request.
$state=$read();$state['csrf'][$key]='legacy-token';$persist($state);
$legacy=TransactionStore::run($c,$tables,$rotated,false,static fn(array &$s,array $a):array=>['csrf'=>$a['csrf']]);
check(is_array($read()['csrf'][$key])&&$legacy['csrf']!==$clean['csrf']);
// Admin checks use freshly locked permission rows; never a browser role.
$c->query('CREATE TABLE permissions(perm_id INT PRIMARY KEY,perm_name VARCHAR(32)) ENGINE=InnoDB');
$c->query('CREATE TABLE user_permissions(perm_user_id INT,perm_perm_id INT,PRIMARY KEY(perm_user_id,perm_perm_id)) ENGINE=InnoDB');
$c->query("INSERT INTO permissions VALUES(1,'admin')");
$moderator=$rotated;$moderator['checkAdmin']=true;
$mt=$tables+['permission'=>'permissions','userPermission'=>'user_permissions'];
$readAdmin=static fn(array &$s,array $a):array=>['admin'=>$a['admin']];
denied(fn()=>TransactionStore::run($c,$mt,$moderator,false,$readAdmin),403);
$c->query('INSERT INTO user_permissions VALUES(1,1)');
check(TransactionStore::run($c,$mt,$moderator,false,$readAdmin)['admin']===true);
$permissionWorker=null;
TransactionStore::run($c,$mt,$moderator,false,function(array &$s,array $a)use(&$permissionWorker):array {
 $permissionWorker=worker('permission');stream_set_blocking($permissionWorker[1][1],false);usleep(150000);
 check(stream_get_contents($permissionWorker[1][1])==='');return [];
});
finish($permissionWorker);
denied(fn()=>TransactionStore::run($c,$mt,$moderator,false,$readAdmin),403);
$c->query('INSERT INTO user_permissions VALUES(1,1)');
$c->query('ALTER TABLE permissions ENGINE=MyISAM');
denied(fn()=>TransactionStore::run($c,$mt,$moderator,false,$readAdmin),503);
echo json_encode(['checks'=>$checks,'schema'=>$schema,'note'=>'isolated DB retained for race tests; no existing tables changed']),"\n";
