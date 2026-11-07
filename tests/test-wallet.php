<?php
declare(strict_types=1);
require __DIR__.'/../Commerce/TestWallet.php';
use GDO\LinkUUp\Commerce\TestWallet as W;
$n=0;
function ok(bool $condition, string $label): void {global $n;if(!$condition)throw new RuntimeException($label);$n++;}
function failUnchanged(array &$ledger, callable $operation, string $label): void {
    $before=$ledger;
    try{$operation($ledger);}catch(Throwable $e){ok($ledger===$before,$label.' atomic');return;}
    throw new RuntimeException($label.' accepted');
}
$l=[];
ok(W::summary($l,'1')===['availableCents'=>0,'reservedCents'=>0] && $l===[],'empty summary no writes');
foreach([0,-1,100001,PHP_INT_MAX] as $amount)failUnchanged($l,fn(array &$subject)=>W::topup($subject,'1',$amount,'topup'),'amount limit');
W::topup($l,'1',2000,'topup1');
ok(W::summary($l,'1')['availableCents']===2000,'20 EUR = 2000 cents');
ok(!W::topup($l,'1',2000,'topup1')['changed'] && count($l['journal'])===1,'topup retry');
failUnchanged($l,fn(array &$subject)=>W::topup($subject,'1',2100,'topup1'),'topup amount mismatch');
failUnchanged($l,fn(array &$subject)=>W::reserve($subject,'1','order1',2100),'insufficient');
W::reserve($l,'1','order1',1500);
ok(W::summary($l,'1')===['availableCents'=>500,'reservedCents'=>1500],'reservation totals');
ok(!W::reserve($l,'1','order1',1500)['changed'],'reserve retry');
failUnchanged($l,fn(array &$subject)=>W::reserve($subject,'1','order1',1400),'reserve mismatch');
failUnchanged($l,fn(array &$subject)=>W::reserve($subject,'2','order1',1500),'foreign reserve');
failUnchanged($l,fn(array &$subject)=>W::settle($subject,'2','order1','released'),'foreign settle');
failUnchanged($l,fn(array &$subject)=>W::settle($subject,'1','order1','refunded'),'refund before capture');
W::settle($l,'1','order1','captured');
ok(W::summary($l,'1')===['availableCents'=>500,'reservedCents'=>0],'capture totals');
ok(!W::settle($l,'1','order1','captured')['changed'],'capture retry');
ok(!W::reserve($l,'1','order1',1500)['changed'],'place retry after capture');
failUnchanged($l,fn(array &$subject)=>W::settle($subject,'1','order1','released'),'release after capture');
W::settle($l,'1','order1','refunded');
ok(W::summary($l,'1')===['availableCents'=>2000,'reservedCents'=>0],'refund totals');
ok(!W::settle($l,'1','order1','refunded')['changed'],'refund retry');
ok(!W::reserve($l,'1','order1',1500)['changed'],'place retry after refund');
failUnchanged($l,fn(array &$subject)=>W::settle($subject,'1','order1','captured'),'capture after refund');
W::reserve($l,'1','order2',2000);W::settle($l,'1','order2','released');
ok(W::summary($l,'1')['availableCents']===2000,'release returns funds');
ok(!W::reserve($l,'1','order2',2000)['changed'],'retry after release');
failUnchanged($l,fn(array &$subject)=>W::settle($subject,'1','order2','refunded'),'refund after release');
$before=$l;$old=W::settle($l,'1','legacy-order','captured');
ok($old['state']==='not_reserved'&&!$old['changed']&&$l===$before,'legacy never charged');
ok(W::summary($l,'2')===['availableCents'=>0,'reservedCents'=>0],'summary isolation');
W::topup($l,'2',2000,'topup1');ok(W::summary($l,'1')['availableCents']===2000,'topup keys scoped to user');
$cap=[];for($i=0;$i<10;$i++)W::topup($cap,'1',100000,'t'.$i);
failUnchanged($cap,fn(array &$subject)=>W::topup($subject,'1',1,'too-much'),'balance limit');
W::reserve($cap,'1','spend',100000);W::settle($cap,'1','spend','captured');W::topup($cap,'1',100000,'refill');
W::settle($cap,'1','spend','refunded');ok(W::summary($cap,'1')['availableCents']===1100000,'refund not blocked by topup cap');
$bad=$l;$bad['accounts'][1]['reservedCents']=1;failUnchanged($bad,fn(array &$subject)=>W::reserve($subject,'1','bad',100),'corrupt reserved total');
ok(count($l['journal'])===$l['sequence'],'journal sequences');
foreach($l['journal'] as $event)ok($event['availableAfterCents']>=0&&$event['reservedAfterCents']>=0,'journal nonnegative');
echo "$n assertions passed\n";
