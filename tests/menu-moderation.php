<?php
require __DIR__.'/../Commerce/Ordering.php';
require __DIR__.'/../Commerce/MenuModeration.php';
use GDO\LinkUUp\Commerce\MenuModeration as M;
$n=0;
function check($x,$m){global $n;if(!$x)throw new RuntimeException($m);$n++;}
function denied($f){try{$f();}catch(RuntimeException|InvalidArgumentException $e){check(true,'denied');return;}throw new LogicException('Unexpected permission');}
foreach(['save-tables','publication','menu-publication','transition','place','topup-test','message','preview-import'] as $a)denied(fn()=>M::check(true,true,false,$a));
foreach(['state','save-menu','mfa-begin','mfa-confirm','mfa-verify','mfa-recover'] as $a){
 M::check(true,true,false,$a);check(true,'allowed');
 denied(fn()=>M::check(false,true,false,$a));denied(fn()=>M::check(true,false,false,$a));denied(fn()=>M::check(true,true,true,$a));
}
$s=['venue'=>['id'=>'8','name'=>'Test','owner'=>'2'],'revision'=>3,'menu'=>[],'orders'=>[['private'=>'order']],'wallet'=>['private'=>'money'],'menuPublished'=>true,'orderingEnabled'=>false];
$row=['id'=>'a','name'=>'Water','category'=>'Drinks','description'=>'','allergens'=>'','price'=>'2.00','available'=>true];
$before=$s;denied(function()use(&$s,$row){M::save($s,'1',[$row],'');});check($s===$before,'no partial mutation');
M::save($s,'1',[$row],'Preis berichtigt');check($s['revision']===4&&count($s['menuReviewHistory'])===1,'revision and audit');check($s['orders']===$before['orders']&&$s['wallet']===$before['wallet'],'no money or order mutation');
check($s['venue']['owner']==='2'&&$s['menuPublished']&&!$s['orderingEnabled'],'no ownership or publication grant');
$p=M::projection($s);check(!isset($p['orders'])&&!isset($p['wallet'])&&!isset($p['venue']['owner']),'no private projection');check($p['canModerate']&&!$p['canManage'],'separate permissions');
$s['menuReviewHistory']=array_fill(0,500,[]);$before=$s;denied(function()use(&$s,$row){M::save($s,'1',[$row],'Capacity check');});check($s===$before,'audit cap atomic');
echo "$n moderation checks passed\n";
