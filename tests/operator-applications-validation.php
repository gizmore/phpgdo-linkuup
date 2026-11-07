<?php
declare(strict_types=1);
require dirname(__DIR__).'/Commerce/OperatorApplications.php';
use GDO\LinkUUp\Commerce\OperatorApplications as A;
$n=0;
function reject(callable $f,int $code):void {global $n;try{$f();throw new Exception('Expected rejection');}catch(RuntimeException $e){if($e->getCode()!==$code)throw $e;++$n;}}
$valid=['business'=>' Kulturhaus ','kind'=>'Kultur / Bildung','address'=>'Beispielweg 1','contact'=>'Testkontakt','email'=>'test@example.invalid','phone'=>'','details'=>'Verantwortliche Leitung'];
assert(A::normalize($valid)['business']==='Kulturhaus');++$n;
foreach(['business','kind','address','contact','email','details'] as $key){$v=$valid;$v[$key]=' ';reject(fn()=>A::normalize($v),400);}
$v=$valid;$v['email']='not-an-email';reject(fn()=>A::normalize($v),400);
$v=$valid;$v['details']="bad\0control";reject(fn()=>A::normalize($v),400);
$v=$valid;$v['business']=str_repeat('ü',161);reject(fn()=>A::normalize($v),400);
$v=$valid;$v['phone']=[];reject(fn()=>A::normalize($v),400);
reject(fn()=>A::submit('1 OR 1',null,$valid),400);
reject(fn()=>A::submit('1','2 OR 1',$valid),400);
reject(fn()=>A::decision('1','2',1,'approved','Geprüft',false),400);
reject(fn()=>A::decision('1','2',1,'owner','Geprüft',true),400);
reject(fn()=>A::decision('1','2',0,'approved','Geprüft',true),400);
reject(fn()=>A::decision('1','2',1,'approved','',true),400);
assert(A::approvalFor('x','1')===false);++$n;
assert(A::approvalFor('1','x')===false);++$n;
echo "$n input/guard checks passed; no database or live service used.\n";
