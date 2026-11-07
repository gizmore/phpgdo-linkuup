<?php
declare(strict_types=1);
require __DIR__.'/../Commerce/Presence.php';
require __DIR__.'/../Commerce/Ordering.php';
use GDO\LinkUUp\Commerce\Presence;
use GDO\LinkUUp\Commerce\Ordering;
$checks = 0;
function check(bool $ok, string $label): void { global $checks; if (!$ok) throw new RuntimeException($label); $checks++; }
$now = 1700000000000;
$fix = ['latitude'=>52.0,'longitude'=>10.0,'accuracy'=>12,'timestamp'=>$now];
$calls = 0;
$inside = static function(float $lat, float $lng) use (&$calls): bool { $calls++; return $lat === 52.0 && $lng === 10.0; };
$status = static fn($p, bool $enabled=true): array => Presence::status($enabled, $p, $now, $inside);
check($status($fix)['canOrder'], 'fresh position within server geometry');
check($status(null)['orderBlockedReason']==='location_required', 'no fix does not allow ordering');
check($status($fix,false)['orderBlockedReason']==='ordering_paused', 'pause takes priority');
foreach (['latitude','longitude','accuracy','timestamp'] as $key) {
    $bad=$fix;unset($bad[$key]);check($status($bad)['orderBlockedReason']==='location_invalid', 'missing '.$key);
    $bad=$fix;$bad[$key]='12';check(!$status($bad)['canOrder'], 'numeric text '.$key);
}
foreach ([['latitude',91],['longitude',181],['accuracy',-1],['timestamp',0],['latitude',INF],['longitude',NAN]] as [$key,$value]) {
    $bad=$fix;$bad[$key]=$value;check($status($bad)['orderBlockedReason']==='location_invalid', 'invalid '.$key);
}
$bad=$fix;$bad['timestamp']=$now-Presence::MAX_AGE_MS-1;check($status($bad)['orderBlockedReason']==='location_stale','old real fix');
$bad=$fix;$bad['timestamp']=$now+Presence::MAX_FUTURE_MS+1;check($status($bad)['orderBlockedReason']==='location_stale','future fix');
$bad=$fix;$bad['accuracy']=Presence::MAX_ACCURACY_METERS+1;check($status($bad)['orderBlockedReason']==='location_inaccurate','inaccurate fix');
$bad=$fix;$bad['latitude']=51.0;$bad['accuracy']=0;check($status($bad)['orderBlockedReason']==='outside_location','accuracy never widens boundary');
$before=$calls;$status(null);$status($fix,false);check($calls===$before,'invalid and paused requests never invoke geometry');
check(Presence::status(true,$fix,$now,static fn()=>1)['canOrder']===false,'geometry must explicitly allow');
try { Presence::requireOrder($status(null)); check(false,'missing fix must throw'); }
catch (RuntimeException $e) { check($e->getCode()===403,'ordering rejection code'); }
Presence::requireOrder($status($fix));check(true,'valid fix allowed');

$s=['venue'=>['id'=>'1','owner'=>'owner'],'menu'=>[['id'=>'tea','name'=>'Tee','priceCents'=>300,'available'=>true]],
    'orders'=>[],'tableCount'=>2,'menuReady'=>true,'tablesReady'=>true,'orderingEnabled'=>false,'menuPublished'=>false];
Ordering::publication($s,'owner',false);check(!$s['menuPublished'],'pausing draft never publishes');
Ordering::publication($s,'owner',true);check($s['menuPublished']&&$s['orderingEnabled'],'explicit release publishes menu and starts orders');
$request=['key'=>'presence-order-123','table'=>'1','lines'=>[['id'=>'tea','qty'=>1]],'expectedTotal'=>300];
$first=Ordering::place($s,'guest',$request);
Ordering::publication($s,'owner',false);check($s['menuPublished']&&!$s['orderingEnabled'],'pause retains published menu');
check(Ordering::place($s,'guest',$request)['id']===$first['id'],'existing idempotent retry remains possible after pause');
check(count($s['orders'])===1,'retry never creates second order');
echo json_encode(['passed'=>$checks,'scope'=>'Fresh GPS validation and publication/pause domain rules; geometry callback stub, no HTTP or physical-presence proof']),PHP_EOL;
