<?php
// Deliberately simulated identities. CLI-only loopback QA, never a production route.
use GDO\LinkUUp\Commerce\Ordering;
use GDO\LinkUUp\Commerce\TestWallet;
require __DIR__.'/../../Commerce/Ordering.php';
require __DIR__.'/../../Commerce/TestWallet.php';
if(PHP_SAPI!=='cli-server'||getenv('LUP_WALLET_QA')!=='1'||($_SERVER['HTTP_HOST']??'')!=='127.0.0.1:8768'||($_SERVER['REMOTE_ADDR']??'')!=='127.0.0.1'){http_response_code(403);exit;}
header('Cache-Control: no-store');
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$assets=['/'=>'index.html','/style.css'=>'style.css','/app.js'=>'app.js','/embed'=>'embed.html'];
if(isset($assets[$path])){header('Content-Type: '.(['/' =>'text/html','/style.css'=>'text/css','/app.js'=>'text/javascript','/embed'=>'text/html'][$path]));readfile(getenv('LUP_WALLET_APP').'/'.$assets[$path]);exit;}
if($path!=='/api'){http_response_code(404);exit;}
header('Content-Type: application/json');
if(($_SERVER['HTTP_ORIGIN']??'')!=='http://127.0.0.1:8768'||($_SERVER['HTTP_X_PREVIEW']??'')!=='local'){http_response_code(403);exit;}
try{
$body=file_get_contents('php://input',false,null,0,200001);if(strlen($body)>200000)throw new RuntimeException('Request too large');
$r=json_decode($body,true,32,JSON_THROW_ON_ERROR);$role=$r['role']??'';if(!in_array($role,['guest','operator'],true))throw new RuntimeException('Test role required');
$user=$role==='operator'?'owner':'guest';$file=getenv('LUP_WALLET_STATE');$lock=fopen($file.'.lock','c+');flock($lock,LOCK_EX);
try{
$store=is_file($file)?json_decode(file_get_contents($file),true,32,JSON_THROW_ON_ERROR):['ledger'=>[],'room'=>['venue'=>['id'=>'qa','name'=>'Studio Bar · Testbetrieb','owner'=>'owner'],'revision'=>1,'menu'=>[['id'=>'spritz','name'=>'Grapefruit Spritz','category'=>'Drinks','description'=>'Grapefruit · Rosmarin · Tonic','allergens'=>'Alkoholfrei','priceCents'=>750,'available'=>true]],'orders'=>[],'tableCount'=>20,'menuReady'=>true,'tablesReady'=>true,'orderingEnabled'=>true]];
$s=&$store['room'];$a=$r['action']??'';$result=[];
switch($a){
case 'state':$result=$s;unset($result['venue']['owner']);$result['orders']=array_values(array_filter($s['orders'],fn($o)=>$role==='operator'||$o['guest']===$user));$result['wallet']=TestWallet::summary($store['ledger'],$user);break;
case 'topup-test':$result=TestWallet::topup($store['ledger'],$user,$r['cents'],$r['key']);break;
case 'place':$result=Ordering::place($s,$user,$r);TestWallet::reserve($store['ledger'],$user,'qa:'.$result['id'],$result['totalCents']);break;
case 'transition':$result=Ordering::transition($s,$user,$r['id'],$r['target']);$target=['delivered'=>'captured','cancelled'=>'released','refunded'=>'refunded'][$result['status']]??null;if($target)TestWallet::settle($store['ledger'],$result['guest'],'qa:'.$result['id'],$target);break;
case 'message':Ordering::message($s,$user,$r['id'],$r['text']);break;
case 'publication':Ordering::publication($s,$user,$r['enabled']);$s['revision']++;break;
default:throw new RuntimeException('QA action unavailable');
}
if($a!=='state'){$tmp=tempnam(dirname($file),'wallet-qa-');chmod($tmp,0600);file_put_contents($tmp,json_encode($store,JSON_THROW_ON_ERROR));rename($tmp,$file);}
}finally{flock($lock,LOCK_UN);fclose($lock);}
echo json_encode($result,JSON_THROW_ON_ERROR);
}catch(Throwable $e){http_response_code(400);echo json_encode(['error'=>$e->getMessage()]);}
