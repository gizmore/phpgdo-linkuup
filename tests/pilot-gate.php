<?php
declare(strict_types=1);
namespace GDO\Core {
 class GDT {}
 class GDT_JSON extends GDT {public array $data;public static function make(): self{return new self();}public function value(array $v):self{$this->data=$v;return $this;}}
 class Application {public static int $code=200;public static function setResponseCode(int $v):void{self::$code=$v;}}
 abstract class MethodAjax {}
}
namespace {
require __DIR__.'/../Method/GastroLocal.php';
if(isset($argv[1])){
 $c=json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR);
 if(array_key_exists('pilot',$c))define('GDO_LINKUUP_GASTRO_LOCAL_PILOT',$c['pilot']);
 if(array_key_exists('topup',$c))define('GDO_LINKUUP_GASTRO_TEST_TOPUP',$c['topup']);
 $_SERVER=['REMOTE_ADDR'=>'127.0.0.1','HTTP_HOST'=>'app.localhost','HTTP_ORIGIN'=>'http://app.localhost','HTTP_X_FORWARDED_FOR'=>'198.51.100.7','HTTP_GDO_LINKUUP_GASTRO_LOCAL_PILOT'=>'true'];
 $m=new ReflectionMethod(\GDO\LinkUUp\Method\GastroLocal::class,'pilotEnabled');
 if($m->invoke(null)!==$c['route'] || $m->invoke(null,true)!==$c['fund'])throw new RuntimeException('Gate mismatch');
 if(!$c['route']){
  // Execute must fail before consulting session, user, room or the datastore.
  $r=(new \GDO\LinkUUp\Method\GastroLocal())->execute();
  if(\GDO\Core\Application::$code!==403 || $r->data['error']!=='Lokaler Gastro-Pilot ist deaktiviert')throw new RuntimeException('Execute bypass');
 }
 exit(0);
}
$cases=[
 ['route'=>false,'fund'=>false],
 ['pilot'=>false,'topup'=>true,'route'=>false,'fund'=>false],
 ['pilot'=>'true','topup'=>true,'route'=>false,'fund'=>false],
 ['pilot'=>1,'topup'=>true,'route'=>false,'fund'=>false],
 ['topup'=>true,'route'=>false,'fund'=>false],
 ['pilot'=>true,'route'=>true,'fund'=>false],
 ['pilot'=>true,'topup'=>'true','route'=>true,'fund'=>false],
 ['pilot'=>true,'topup'=>true,'route'=>true,'fund'=>true],
];
foreach($cases as $case){$p=proc_open([PHP_BINARY,__FILE__,json_encode($case)],[], $pipes);if(!is_resource($p)||proc_close($p)!==0)throw new RuntimeException('Child failed');}
echo count($cases)." gate scenarios passed (isolated PHP, spoofed proxy headers; not a deployed proxy test)\n";
}
