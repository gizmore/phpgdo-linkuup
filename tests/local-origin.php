<?php
namespace GDO\Core { abstract class MethodAjax {} }
namespace {
require __DIR__.'/../Commerce/Access.php';
require __DIR__.'/../Method/GastroLocal.php';
$m=new ReflectionMethod(\GDO\LinkUUp\Method\GastroLocal::class,'localAppRequest');
$cases=[
 [['HTTP_HOST'=>'app.localhost'],true],
 [['HTTP_HOST'=>'localhost'],false],
 [['HTTP_HOST'=>'localhost','HTTP_ORIGIN'=>'http://app.localhost'],true],
 [['HTTP_HOST'=>'localhost','HTTP_ORIGIN'=>'https://evil.invalid'],false],
 [['HTTP_HOST'=>'localhost','HTTP_REFERER'=>'http://app.localhost/preview/gastro/embed.html'],true],
 [['HTTP_HOST'=>'localhost','HTTP_REFERER'=>'http://app.localhost.evil.invalid/'],false],
 [['HTTP_HOST'=>'localhost','HTTP_REFERER'=>'http://app.localhost:8080/'],false],
 [['HTTP_HOST'=>'localhost','HTTP_REFERER'=>'https://app.localhost/'],false],
 [['HTTP_HOST'=>'remote.invalid','HTTP_ORIGIN'=>'http://app.localhost'],false],
 [['HTTP_HOST'=>'localhost','HTTP_X_FORWARDED_HOST'=>'app.localhost'],false],
];
foreach($cases as [$server,$expected]){$_SERVER=$server;if($m->invoke(null)!==$expected)throw new RuntimeException('Host/origin mismatch');}
echo count($cases)." local host/referrer checks passed (helper only)\n";
}
