<?php
require __DIR__.'/../Commerce/Access.php';
use GDO\LinkUUp\Commerce\Access;
$n=0;$token=str_repeat('a',64);
function test($active,$owner,$inactive,$action,$method,$origin,$sent,$allowed){
 global $n,$token;$ok=true;
 try{Access::check($active,$owner,$inactive,$action,$method,$origin,$token,$sent);}catch(RuntimeException $e){$ok=false;}
 if($ok!==$allowed)throw new RuntimeException("Policy mismatch: $action");$n++;
}
foreach(['guest','foreign-guest','owner','foreign-owner'] as $role){
 $own=$role==='owner';
 foreach(['state','save-menu','save-tables','publication','menu-publication','transition','topup-test','place','message'] as $action){
  $allowed=!in_array($action,['save-menu','save-tables','publication','menu-publication','transition'])||$own;
  test(true,$own,false,$action,'POST','http://app.localhost',$token,$allowed);
  test(false,$own,false,$action,'POST','http://app.localhost',$token,false);
  if($action!=='state'){
   test(true,$own,false,$action,'GET','http://app.localhost',$token,false);
   test(true,$own,false,$action,'POST','https://foreign.invalid',$token,false);
   test(true,$own,false,$action,'POST','http://app.localhost','',false);
  }
 }
}
foreach(['save-menu','save-tables','publication','menu-publication','place','topup-test'] as $a)test(true,true,true,$a,'POST','http://app.localhost',$token,false);
foreach(['state','transition','message'] as $a)test(true,true,true,$a,'POST','http://app.localhost',$token,true);
test(true,true,false,'grant-owner','POST','http://app.localhost',$token,false);
test(true,true,false,'state','DELETE','http://app.localhost',$token,false);
echo "$n policy assertions passed (isolated inputs, not GDO login)\n";
