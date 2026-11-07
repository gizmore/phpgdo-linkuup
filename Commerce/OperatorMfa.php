<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Operator step-up. Caller holds user/session/store locks and persists failures too. */
final class OperatorMfa
{
    public static function code(string $secret, int $step, int $digits=6): string
    {
        $hash=hash_hmac('sha1',pack('N2',intdiv($step,4294967296),$step%4294967296),$secret,true);
        $offset=ord($hash[19])&15;
        $number=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;
        return str_pad((string)($number%(10**$digits)),$digits,'0',STR_PAD_LEFT);
    }
    public static function base32(string $raw): string
    {
        $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$out='';$buffer=0;$bits=0;
        foreach(str_split($raw) as $byte){$buffer=($buffer<<8)|ord($byte);$bits+=8;while($bits>=5){$bits-=5;$out.=$alphabet[($buffer>>$bits)&31];}$buffer&=(1<<$bits)-1;}
        if($bits)$out.=$alphabet[($buffer<<(5-$bits))&31];return $out;
    }
    private static function key(): string
    {
        $value=getenv('LINKUUP_MFA_KEY');$key=is_string($value)?base64_decode($value,true):false;
        if(!is_string($key)||strlen($key)!==SODIUM_CRYPTO_SECRETBOX_KEYBYTES)throw new \RuntimeException('Betreiberschutz ist serverseitig noch nicht eingerichtet',503);
        return $key;
    }
    private static function seal(string $raw): string {$nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);return base64_encode($nonce.sodium_crypto_secretbox($raw,$nonce,self::key()));}
    private static function unseal(string $value): string
    {
        $raw=base64_decode($value,true);if(!is_string($raw)||strlen($raw)<40)throw new \RuntimeException('Betreiberschutz nicht verfügbar',503);
        $secret=sodium_crypto_secretbox_open(substr($raw,24),substr($raw,0,24),self::key());
        if($secret===false)throw new \RuntimeException('Betreiberschutz nicht verfügbar',503);return $secret;
    }
    public static function status(array $store,string $uid,string $binding,int $now): array
    {
        $a=$store['operatorMfa'][$uid]??[];
        return ['enabled'=>!empty($a['secret']),'verified'=>($a['grants'][$binding]??0)>$now];
    }
    public static function handle(array &$store,string $uid,string $binding,string $action,array $request,bool $passwordOK,int $now): array
    {
        self::key();
        $store['operatorMfa'][$uid]??=['grants'=>[],'attempts'=>0,'blockedUntil'=>0];$a=&$store['operatorMfa'][$uid];
        foreach($a['grants'] as $key=>$expires)if($expires<=$now)unset($a['grants'][$key]);
        if(($a['blockedUntil']??0)>$now)return ['error'=>'Zu viele Versuche. Bitte in 15 Minuten erneut versuchen.'];
        if(($a['blockedUntil']??0)>0){$a['attempts']=0;$a['blockedUntil']=0;}
        $fail=static function()use(&$a,$now){$a['attempts']=($a['attempts']??0)+1;if($a['attempts']>=5)$a['blockedUntil']=$now+900;return ['error'=>'Bestätigung fehlgeschlagen.'];};
        if($action==='mfa-begin'){
            if(!empty($a['secret'])||!$passwordOK)return $fail();
            // Replace only an unconfirmed enrollment; never reset an active factor.
            $secret=random_bytes(20);$a['pending']=self::seal($secret);$a['pendingExpires']=$now+600;$a['pendingBinding']=$binding;$a['pendingReplace']=false;
            return ['enrollmentSecret'=>self::base32($secret),'expiresIn'=>600];
        }
        if(!in_array($action,['mfa-confirm','mfa-verify','mfa-recover'],true))return ['error'=>'Unbekannte Sicherheitsaktion.'];
        if($action==='mfa-recover'){
            if(!$passwordOK||empty($a['secret']))return $fail();
            $code=strtoupper(trim((string)($request['code']??'')));$hash=hash('sha256',$code);$found=null;
            foreach($a['recovery']??[] as $i=>$value)if(hash_equals($value,$hash))$found=$i;
            if($found===null)return $fail();
            unset($a['recovery'][$found]);$a['recovery']=array_values($a['recovery']);
            // Recovery invalidates every prior step-up grant, never disables MFA.
            $a['grants']=[$binding=>$now+900];$a['attempts']=0;
            unset($a['pending'],$a['pendingExpires'],$a['pendingBinding'],$a['pendingReplace']);
            if (($request['replace']??false)===true) {
                $secret=random_bytes(20);$a['pending']=self::seal($secret);
                $a['pendingExpires']=$now+600;$a['pendingBinding']=$binding;$a['pendingReplace']=true;
                return ['enrollmentSecret'=>self::base32($secret),'expiresIn'=>600];
            }
            return ['verified'=>true,'recoveryRemaining'=>count($a['recovery'])];
        }
        $confirm=$action==='mfa-confirm';
        if($confirm && ((!empty($a['secret'])&&empty($a['pendingReplace']))||($a['pendingExpires']??0)<=$now||($a['pendingBinding']??'')!==$binding))return $fail();
        $sealed=$confirm?($a['pending']??null):($a['secret']??null);
        if(!$sealed)return $fail();
        $secret=self::unseal($sealed);$code=$request['code']??'';$matched=null;
        if(is_string($code)&&preg_match('/^[0-9]{6}$/D',$code)){
            $step=intdiv($now,30);
            foreach([$step-1,$step,$step+1] as $candidate)if($candidate>($confirm?-1:($a['lastStep']??-1))&&hash_equals(self::code($secret,$candidate),$code))$matched=$candidate;
        }
        if($matched===null)return $fail();
        $a['lastStep']=$matched;$a['attempts']=0;$a['grants'][$binding]=$now+900;
        $result=['verified'=>true];
        if($confirm){
            $a['grants']=[$binding=>$now+900];$a['secret']=$sealed;unset($a['pending'],$a['pendingExpires'],$a['pendingBinding'],$a['pendingReplace']);$a['recovery']=[];$codes=[];
            for($i=0;$i<8;$i++){$value=strtoupper(bin2hex(random_bytes(10)));$codes[]=$value;$a['recovery'][]=hash('sha256',$value);}
            $result['recoveryCodes']=$codes;
        }
        return $result;
    }
}
