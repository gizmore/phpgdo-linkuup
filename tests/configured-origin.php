<?php
declare(strict_types=1);
namespace GDO\Core { abstract class MethodAjax {} }
namespace {
require __DIR__.'/../Commerce/Access.php';require __DIR__.'/../Method/GastroLocal.php';
if(isset($argv[1])){
 $c=json_decode($argv[1],true,flags:JSON_THROW_ON_ERROR);define('GDO_LINKUUP_GASTRO_ORIGIN',$c['origin']);
 try {$o=\GDO\LinkUUp\Commerce\Access::origin();if(!$c['valid'])throw new \LogicException('Invalid config accepted');}
 catch(\RuntimeException $e){if($c['valid']||$e->getCode()!==503)throw $e;exit;}
 $m=new \ReflectionMethod(\GDO\LinkUUp\Method\GastroLocal::class,'localAppRequest');
 $host=substr($o,7);
 foreach([
  [['HTTP_HOST'=>$host,'HTTP_ORIGIN'=>$o],true],
  [['HTTP_HOST'=>$host,'HTTP_REFERER'=>$o.'/preview/gastro/embed.html'],true],
  [['HTTP_HOST'=>$host],false],
  [['HTTP_HOST'=>$host,'HTTP_ORIGIN'=>'https://evil.invalid','HTTP_REFERER'=>$o.'/'],false],
  [['HTTP_HOST'=>'evil.invalid','HTTP_ORIGIN'=>$o],false],
  [['HTTP_HOST'=>$host,'HTTP_REFERER'=>$o.'.evil.invalid/'],false],
 ] as [$s,$expected]){$_SERVER=$s;if($m->invoke(null)!==$expected)throw new \RuntimeException('Origin boundary mismatch');}
 exit;
}
$cases=[['origin'=>'http://127.0.0.1:18769','valid'=>true],['origin'=>'http://localhost:18080','valid'=>true]];
foreach(['https://evil.invalid','http://127.0.0.1.evil.invalid','http://localhost:65536','http://localhost/path','http://user@localhost','http://localhost:0',true,''] as $o)$cases[]=['origin'=>$o,'valid'=>false];
foreach($cases as $c){$p=proc_open([PHP_BINARY,__FILE__,json_encode($c)],[], $pipes);if(!is_resource($p)||proc_close($p)!==0)throw new \RuntimeException('Case failed');}
echo "10 trusted-origin configurations and 12 request-boundary cases passed\n";
}
