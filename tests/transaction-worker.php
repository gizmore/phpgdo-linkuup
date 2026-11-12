<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||!str_starts_with(getenv('GASTRO_QA_SOCKET')?:'', '/tmp/simion-gastro-db-'))exit(2);
require __DIR__.'/../Commerce/TransactionStore.php';require __DIR__.'/../Commerce/TestWallet.php';
$r=json_decode(stream_get_contents(STDIN),true,64,JSON_THROW_ON_ERROR);
if(!preg_match('/^simion_gastro_qa_[a-f0-9]{12}$/D',$r['schema']))exit(3);
$c=new mysqli('localhost',getenv('USER'),'',$r['schema'],0,getenv('GASTRO_QA_SOCKET'));
echo "READY\n";flush();
if($r['mode']==='logout')$c->query('UPDATE sessions SET sess_user=NULL WHERE sess_id=1');
elseif($r['mode']==='deactivate')$c->query('UPDATE users SET user_deleted=NOW() WHERE user_id=1');
elseif($r['mode']==='room')$c->query('UPDATE rooms SET room_enabled=0 WHERE room_id=1');
elseif($r['mode']==='permission')$c->query('DELETE FROM user_permissions WHERE perm_user_id=1');
elseif($r['mode']==='topup')\GDO\LinkUUp\Commerce\TransactionStore::run($c,$r['tables'],$r['identity'],true,static function(array &$s):array{return \GDO\LinkUUp\Commerce\TestWallet::topup($s['ledger'],'1',100,'parallel-same-key');});
else exit(4);
echo "DONE\n";
