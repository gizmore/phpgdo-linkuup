<?php
require __DIR__.'/../Commerce/OperatorMfa.php';
use GDO\LinkUUp\Commerce\OperatorMfa as M;
putenv('LINKUUP_MFA_KEY='.base64_encode(random_bytes(32)));
$n=0;function check($yes,$label){global $n;if(!$yes)throw new RuntimeException($label);$n++;}
check(M::code('12345678901234567890',1,8)==='94287082','RFC6238 SHA1 vector');
check(M::base32('foo')==='MZXW6','base32 vector');
function decode32($s){$bits='';foreach(str_split($s) as $c)$bits.=str_pad(decbin(strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',$c)),5,'0',STR_PAD_LEFT);$out='';foreach(str_split($bits,8) as $b)if(strlen($b)===8)$out.=chr(bindec($b));return $out;}
$s=[];$now=1700000010;
$r=M::handle($s,'u','a','mfa-begin',[],false,$now);check(isset($r['error']),'password required');
$r=M::handle($s,'u','a','mfa-begin',[],true,$now);$secret=decode32($r['enrollmentSecret']);
check(!str_contains(json_encode($s),$r['enrollmentSecret']),'no plaintext enrollment stored');
$code=M::code($secret,intdiv($now,30));
check(isset(M::handle($s,'u','b','mfa-confirm',['code'=>$code],false,$now)['error']),'enrollment bound to session');
$r=M::handle($s,'u','a','mfa-confirm',['code'=>$code],false,$now);$recovery=$r['recoveryCodes'];
check(count($recovery)===8&&M::status($s,'u','a',$now)['verified'],'enrollment confirmed');
check(!M::status($s,'u','b',$now)['verified'],'separate device must verify');
check(isset(M::handle($s,'u','b','mfa-verify',['code'=>$code],false,$now)['error']),'TOTP replay rejected');
check(!str_contains(json_encode($s),$recovery[0]),'recovery only hashed');
$r=M::handle($s,'u','b','mfa-recover',['code'=>$recovery[0]],true,$now);
check($r['verified']&&!M::status($s,'u','a',$now)['verified'],'recovery invalidates grants');
check(isset(M::handle($s,'u','c','mfa-recover',['code'=>$recovery[0]],true,$now)['error']),'recovery replay rejected');
check(!M::status($s,'u','b',$now+901)['verified'],'grant expires');
for($i=0;$i<5;$i++)M::handle($s,'u','b','mfa-verify',['code'=>'bad'],false,$now);
check(isset(M::handle($s,'u','b','mfa-recover',['code'=>$recovery[1]],true,$now)['error']),'rate limit persists');
check(isset(M::handle($s,'u','b','mfa-begin',[],true,$now+901)['error']),'active factor cannot be replaced via enrollment');
$now+=930;
$r=M::handle($s,'u','new','mfa-recover',['code'=>$recovery[1],'replace'=>true],true,$now);
check(isset($r['enrollmentSecret']),'recovery can prepare replacement');$replacement=decode32($r['enrollmentSecret']);
check(isset(M::handle($s,'u','other','mfa-confirm',['code'=>M::code($replacement,intdiv($now,30))],false,$now)['error']),'replacement bound to recovering session');
$r=M::handle($s,'u','new','mfa-confirm',['code'=>M::code($replacement,intdiv($now,30))],false,$now);
check(count($r['recoveryCodes'])===8,'replacement issues fresh recovery codes');
check(isset(M::handle($s,'u','other','mfa-recover',['code'=>$recovery[2]],true,$now)['error']),'old recovery codes invalid after replacement');
check(isset(M::handle($s,'u','other','mfa-verify',['code'=>M::code($secret,intdiv($now+30,30))],false,$now+30)['error']),'old authenticator invalid after replacement');
$newCodes=$r['recoveryCodes'];
$pending=M::handle($s,'u','A','mfa-recover',['code'=>$newCodes[0],'replace'=>true],true,$now+60);
$pendingSecret=decode32($pending['enrollmentSecret']);
M::handle($s,'u','B','mfa-recover',['code'=>$newCodes[1]],true,$now+60);
check(!M::status($s,'u','A',$now+60)['verified'],'second recovery revokes first grant');
check(isset(M::handle($s,'u','A','mfa-confirm',['code'=>M::code($pendingSecret,intdiv($now+60,30))],false,$now+60)['error']),'second recovery invalidates previous pending replacement');
check(M::status($s,'u','B',$now+60)['verified'],'second recovery keeps its grant');
putenv('LINKUUP_MFA_KEY');try{M::handle($s,'u','a','mfa-verify',[],false,$now);check(false,'missing key');}catch(RuntimeException $e){check($e->getCode()===503,'missing key fails closed');}
echo json_encode(['passed'=>$n,'scope'=>'TOTP vectors, isolated enrollment/recovery/rate-limit/session rules; not HTTP integration']),PHP_EOL;
