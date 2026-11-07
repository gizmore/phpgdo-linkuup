<?php
require __DIR__.'/../Commerce/PilotStore.php';
require __DIR__.'/../Commerce/Ordering.php';
require __DIR__.'/../Commerce/TestWallet.php';
use GDO\LinkUUp\Commerce\PilotStore as S;
use GDO\LinkUUp\Commerce\Ordering as O;
use GDO\LinkUUp\Commerce\TestWallet as W;
if(!function_exists('pcntl_fork'))throw new RuntimeException('pcntl required');
$dir=sys_get_temp_dir().'/gastro-concurrency-'.bin2hex(random_bytes(8));mkdir($dir,0700);$n=0;
function check($v,$m){global $n;if(!$v)throw new RuntimeException($m);$n++;}
function readStore(){global $dir;return S::run($dir,false,fn(&$s)=>$s);}
function concurrent($fn,$count=8){
 $pids=[];$start=microtime(true)+0.2;
 for($i=0;$i<$count;$i++){
  $pid=pcntl_fork();if($pid===-1)throw new RuntimeException('fork failed');
  if($pid===0){while(microtime(true)<$start)usleep(1000);try{$fn($i);exit(0);}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}}$pids[]=$pid;
 }
 foreach($pids as $pid){pcntl_waitpid($pid,$status);check(pcntl_wifexited($status)&&pcntl_wexitstatus($status)===0,'child failed');}
}
function settle($target){global $dir;return S::run($dir,true,function(&$s)use($target){$r=&$s['rooms']['1'];$o=O::transition($r,'owner',$r['orders'][0]['id'],$target);W::settle($s['ledger'],'guest','1:'.$o['id'],['delivered'=>'captured','cancelled'=>'released'][$target]);return $o;});}
try{
 S::run($dir,true,function(&$s){$s['rooms']['1']=['venue'=>['id'=>'1','owner'=>'owner'],'orderingEnabled'=>true,'tableCount'=>20,'menu'=>[['id'=>'food','name'=>'Food','priceCents'=>1400,'available'=>true]],'orders'=>[]];W::topup($s['ledger'],'guest',2000,'initial');return [];});
 $request=['key'=>'same-order-key-123','table'=>'8','lines'=>[['id'=>'food','qty'=>1]],'expectedTotal'=>1400];
 concurrent(function()use($dir,$request){S::run($dir,true,function(&$s)use($request){$o=O::place($s['rooms']['1'],'guest',$request);W::reserve($s['ledger'],'guest','1:'.$o['id'],$o['totalCents']);return $o;});});
 $s=readStore();check(count($s['rooms']['1']['orders'])===1,'one order');check(count($s['ledger']['journal'])===2,'one reservation');check(W::summary($s['ledger'],'guest')===['availableCents'=>600,'reservedCents'=>1400],'wallet conserved');
 $before=file_get_contents($dir.'/pilot-v2.json');
 try{S::run($dir,true,function(&$s)use($request){$request['key']='insufficient-order-123';$o=O::place($s['rooms']['1'],'guest',$request);W::reserve($s['ledger'],'guest','1:'.$o['id'],$o['totalCents']);return $o;});throw new LogicException('should reject');}catch(RuntimeException $e){}
 check(file_get_contents($dir.'/pilot-v2.json')===$before,'insufficient funds rolls back order/history');
 S::run($dir,true,function(&$s){$r=&$s['rooms']['1'];$id=$r['orders'][0]['id'];O::transition($r,'owner',$id,'accepted');O::transition($r,'owner',$id,'preparing');return [];});
 $preparing=readStore();
 concurrent(fn()=>settle('delivered'));
 $s=readStore();check(count($s['ledger']['journal'])===3,'single capture');check(count($s['rooms']['1']['orders'][0]['history'])===4,'single delivery history');check(W::summary($s['ledger'],'guest')===['availableCents'=>600,'reservedCents'=>0],'capture totals');
 S::run($dir,true,function(&$s)use($preparing){$s=$preparing;return [];});
 concurrent(function($i){try{settle($i%2?'delivered':'cancelled');}catch(RuntimeException $e){if($e->getMessage()!=='Unzulässiger Statuswechsel')throw $e;}});
 $s=readStore();$status=$s['rooms']['1']['orders'][0]['status'];check(in_array($status,['delivered','cancelled'],true),'single winning state');check(count($s['ledger']['journal'])===3,'race one settlement');check(count($s['rooms']['1']['orders'][0]['history'])===4,'race one history entry');check(W::summary($s['ledger'],'guest')===['availableCents'=>$status==='delivered'?600:2000,'reservedCents'=>0],'race ledger matches order');
 echo "$n assertions passed: 8 concurrent retries, duplicate delivery, delivery/cancel race, rollback. Local PilotStore only; no DB or GDO login test.\n";
}finally{foreach(glob($dir.'/*') as $f)unlink($f);rmdir($dir);}
