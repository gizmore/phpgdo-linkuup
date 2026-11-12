<?php
require __DIR__.'/../Commerce/PublicMenu.php';
use GDO\LinkUUp\Commerce\PublicMenu as P;
$n=0;function check($v){global $n;if(!$v)throw new RuntimeException('Public card assertion failed');$n++;}
$s=['menuPublished'=>true,'orderingEnabled'=>true,'menu'=>[['id'=>'a','name'=>'Water','priceCents'=>200,'private'=>'secret']],'orders'=>['private'],'csrf'=>'private','wallet'=>['private'],'menuReviewHistory'=>['private']];
$r=P::project('1','Bar',$s,true);check(count($r['menu'])===1);check($r['readOnly']&&!$r['canManage']&&!$r['canOrder']);check($r['orders']===[]);foreach(['csrf','wallet','menuReviewHistory'] as $k)check(!array_key_exists($k,$r));check(!array_key_exists('private',$r['menu'][0]));
foreach([false,true] as $eligible)foreach([false,true] as $published){$s['menuPublished']=$published;$r=P::project('1','Bar',$s,$eligible);check($r['menuPublished']===($eligible&&$published));check(count($r['menu'])===($eligible&&$published?1:0));check($r['orderingEnabled']===($eligible&&$published));}
$r=P::project('1','Bar',null,true);check(!$r['menuPublished']&&$r['menu']===[]);echo "$n public projection checks passed\n";
