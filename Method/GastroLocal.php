<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;
use GDO\Core\GDT;
use GDO\Core\Application;
use GDO\Core\GDT_JSON;
use GDO\Core\GDT_Object;
use GDO\Core\MethodAjax;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Commerce\Ordering;
use GDO\LinkUUp\Commerce\MenuModeration;
use GDO\LinkUUp\Commerce\OperatorMfa;
use GDO\LinkUUp\Commerce\Access;
use GDO\LinkUUp\Commerce\TransactionStore;
use GDO\DB\Database;
use GDO\LinkUUp\Commerce\TestWallet;
use GDO\LinkUUp\Commerce\Presence;
use GDO\LinkUUp\Commerce\OperatorApplications;
use GDO\Session\GDO_Session;
use GDO\User\GDO_User;

/** Authenticated localhost pilot. No payment provider and no production deployment. */
final class GastroLocal extends MethodAjax
{
    public static function pilotEnabled(bool $topup=false): bool
    {
        // Only trusted server configuration can opt into this isolated pilot.
        // Never infer permission from Host/Origin or other client headers.
        return defined('GDO_LINKUUP_GASTRO_LOCAL_PILOT')
            && constant('GDO_LINKUUP_GASTRO_LOCAL_PILOT') === true
            && (!$topup || (defined('GDO_LINKUUP_GASTRO_TEST_TOPUP')
                && constant('GDO_LINKUUP_GASTRO_TEST_TOPUP') === true));
    }
    public static function localAppRequest(): bool
    {
        $host=$_SERVER['HTTP_HOST']??'';
        $expected=Access::origin();
        if ($expected!=='http://app.localhost') {
            if ($host!==substr($expected,7)) return false;
            if (isset($_SERVER['HTTP_ORIGIN'])) return $_SERVER['HTTP_ORIGIN']===$expected;
            $ref=parse_url($_SERVER['HTTP_REFERER']??'');
            $origin=is_array($ref)&&isset($ref['scheme'],$ref['host'])?$ref['scheme'].'://'.$ref['host'].(isset($ref['port'])?':'.$ref['port']:''):'';
            return $origin===$expected;
        }
        if ($host==='app.localhost') return true;
        // The local /backend proxy forwards Host: localhost. Require the actual
        // app origin/referrer as well; never accept arbitrary forwarded headers.
        if ($host!=='localhost') return false;
        if (($_SERVER['HTTP_ORIGIN']??'')==='http://app.localhost') return true;
        $ref=parse_url($_SERVER['HTTP_REFERER']??'');
        return is_array($ref) && ($ref['scheme']??'')==='http' && ($ref['host']??'')==='app.localhost' && !isset($ref['port']);
    }
    public function gdoParameters(): array { return [GDT_Object::make('room')->table(LUP_Room::table())->notNull()]; }
    public function hasPermission(GDO_User $user, string &$error, array &$args): bool
    {
        $allowed=self::pilotEnabled() && in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true)
            && self::localAppRequest()
            && in_array($_SERVER['HTTP_ORIGIN']??'', ['', Access::origin()],true) && $user->isMember() && $user->gdoVar('user_deleted')===null
            && GDO_Session::instance() && $this->gdoParameterValue('room');
        if (!$allowed) $error='err_not_allowed';
        return (bool)$allowed;
    }
    public function execute(): GDT
    {
        header('Cache-Control: no-store');
        try {
            if (!self::pilotEnabled()) throw new \RuntimeException('Lokaler Gastro-Pilot ist deaktiviert',403);
            $user=GDO_User::current();$room=LUP_Room::table()->getById($this->gdoParameterValue('room')->getID());
            if (!$room || !$user->isMember() || $user->gdoVar('user_deleted')!==null) throw new \RuntimeException('Anmeldung erforderlich');
            $uid=(string)$user->getID();$rid=(string)$room->getID();
            if (!ctype_digit($rid)) throw new \RuntimeException('Ungültige Location');
            $method=$_SERVER['REQUEST_METHOD']??'GET';$r=[];
            if($method==='POST') {
                if(($_SERVER['HTTP_ORIGIN']??'')!==Access::origin() || (int)($_SERVER['CONTENT_LENGTH']??0)>200000) throw new \RuntimeException('Ungültige Anfrage');
                $body=file_get_contents('php://input',false,null,0,200001);
                if($body===false || strlen($body)>200000)throw new \RuntimeException('Anfrage zu groß');
                $r=json_decode($body,true,32,JSON_THROW_ON_ERROR);
                if(!is_array($r))throw new \RuntimeException('Ungültige Anfrage');
            } elseif($method!=='GET')throw new \RuntimeException('Methode nicht erlaubt');
            $action=$r['action']??'state';
            if ($action==='topup-test' && !self::pilotEnabled(true)) throw new \RuntimeException('Testaufladung ist deaktiviert',403);
            if (!is_string($action)) throw new \RuntimeException('Ungültige Aktion');
            $session=GDO_Session::instance();
            if (!$session) throw new \RuntimeException('Sitzung erforderlich',403);
            if (GDO_DB_READONLY) throw new \RuntimeException('Speicher ist schreibgeschützt',503);
            $db=Database::instance();$db->queryRead('SELECT 1');
            if (!($db->link instanceof \mysqli)) throw new \RuntimeException('MariaDB-Pilot erforderlich',503);
            $moderation=($_GET['moderation']??'')==='1';
            $tables=['session'=>GDO_Session::table()->gdoTableName(),'user'=>GDO_User::table()->gdoTableName(),'room'=>LUP_Room::table()->gdoTableName(),'store'=>'lup_gastro_store'];
            if($moderation){$tables['permission']=\GDO\User\GDO_Permission::table()->gdoTableName();$tables['userPermission']=\GDO\User\GDO_UserPermission::table()->gdoTableName();}
            $identity=['checkAdmin'=>$moderation,'session'=>$session->getID(),'token'=>$session->getToken(),'user'=>$uid,'room'=>$rid,'csrf'=>(string)($_SERVER['HTTP_X_GASTRO_CSRF']??''),'maxAge'=>GDO_SESS_TIME,'requireCsrf'=>$action!=='state','allowCsrfInit'=>$action==='state'];
            $result=TransactionStore::run($db->link,$tables,$identity,$action!=='state',function(array &$store,array $authority) use($rid,$action,$r,$uid,$moderation): array {
                // These rows stay locked through the order/ledger commit.
                $freshUser=GDO_User::blank($authority['user'])->setPersisted(true);
                $room=LUP_Room::blank($authority['room']);
                $token=$authority['csrf'];
                $owner=(string)$room->getOwnerID();$approved=OperatorApplications::approvalFor($owner,$rid);$manage=$room->isOwner($freshUser) && $approved;
                $inactive=$room->isDisabled() || $room->gdoValue('room_deleted')!==null;
                if($moderation)MenuModeration::check($authority['admin']===true,(bool)$approved,$inactive,$action);
                Access::check($freshUser->isMember() && $freshUser->gdoVar('user_deleted')===null, $manage||$moderation, $inactive, $action, $_SERVER['REQUEST_METHOD']??'GET', $_SERVER['HTTP_ORIGIN']??'', $token, (string)($_SERVER['HTTP_X_GASTRO_CSRF']??''));
                $mfa=OperatorMfa::status($store,$uid,$authority['binding'],time());
                if (str_starts_with($action,'mfa-')) {
                    if(!$manage&&!$moderation)throw new \RuntimeException('Keine Betreiberberechtigung',403);
                    $passwordOK=false;
                    if(in_array($action,['mfa-begin','mfa-recover'],true)) {
                        $hash=\GDO\Login\Module_Login::instance()->userSettingVar($freshUser,'password');
                        $password=$r['password']??'';
                        $passwordOK=is_string($password)&&strlen($password)<=1024&&is_string($hash)&&password_verify($password,$hash);
                    }
                    return OperatorMfa::handle($store,$uid,$authority['binding'],$action,$r,$passwordOK,time());
                }
                if(($manage||$moderation) && !$mfa['verified'] && $action!=='state')throw new \RuntimeException('Betreiberzugriff bitte mit Authenticator bestätigen',403);
                $store['rooms'][$rid] ??= ['venue'=>['id'=>$rid,'name'=>$room->getName(),'owner'=>$owner],'revision'=>1,'menu'=>[],'orders'=>[],'tableCount'=>20,'menuReady'=>false,'tablesReady'=>false,'menuPublished'=>false,'orderingEnabled'=>false];
                $s=&$store['rooms'][$rid];
                // Legacy enabled menus were already published. A never-published
                // draft is not made public merely because it contains rows.
                $s['menuPublished'] ??= ($s['orderingEnabled'] ?? false) === true;
                // Authority is always resolved from GDO, never from browser or old persisted owner.
                $s['venue']=['id'=>$rid,'name'=>$room->getName(),'owner'=>$owner];
                $presence = Presence::status(!$inactive && $approved && $s['orderingEnabled'] === true, $r['position'] ?? null,
                    (int)floor(microtime(true) * 1000),
                    static fn(float $lat, float $lng): bool => $room->isInChatRange($lat, $lng, null));
                if(in_array($action,['save-menu','save-tables','publication','menu-publication'],true)&&($r['revision']??null)!==$s['revision'])throw new \RuntimeException('Stand geändert. Bitte aktualisieren.');
                if($moderation) {
                    if($action==='state') {
                        $result=$mfa['verified']?MenuModeration::projection($s):['venue'=>['id'=>$rid,'name'=>$room->getName()],'canManage'=>false,'canModerate'=>true,'menu'=>[]];
                        return $result+['csrf'=>$token,'mfa'=>$mfa,'localPilot'=>true];
                    }
                    MenuModeration::save($s,$uid,$r['rows']??[],is_string($r['reason']??null)?$r['reason']:'');
                    return [];
                }
                $result=[];
                switch($action) {
                    case 'state':if($manage&&!$mfa['verified'])return ['venue'=>['id'=>$rid,'name'=>$room->getName()],'canManage'=>true,'csrf'=>$token,'mfa'=>$mfa,'orders'=>[],'menu'=>[],'canOrder'=>false,'orderBlockedReason'=>'operator_verification_required','localPilot'=>true];$result=$s;$result['wallet']=TestWallet::summary($store['ledger'],$uid);$result['orders']=array_values(array_filter($s['orders'],fn($o)=>$o['venue']===$rid&&($manage||$o['guest']===$uid)));unset($result['venue']['owner']);if(!$manage)unset($result['menuReviewHistory']);$result['canManage']=$manage;if($manage)$result['mfa']=$mfa;$result['csrf']=$token;$result['localPilot']=true;$result['canOrder']=$presence['canOrder'];$result['orderBlockedReason']=$presence['orderBlockedReason'];if($inactive||!$approved){$result['orderingEnabled']=false;$result['menuPublished']=false;}if(!$manage&&($inactive||!$approved||!$result['menuPublished']))$result['menu']=[];break;
                    case 'preview-import':if(!is_string($r['csv']??null))throw new \InvalidArgumentException('CSV erforderlich');$result=['rows'=>\GDO\LinkUUp\Commerce\MenuImport::parse($r['csv'])];break;
                    case 'save-menu':$s['menu']=Ordering::menu($r['rows']??[]);$s['menuReady']=true;if(!array_filter($s['menu'],fn($i)=>$i['available']))$s['orderingEnabled']=false;$s['revision']++;break;
                    case 'save-tables':if(!is_int($r['count']??null))throw new \RuntimeException('Ganzzahl erforderlich');Ordering::tables($s,$uid,$r['count']);$s['revision']++;break;
                    case 'menu-publication':if(!is_bool($r['enabled']??null))throw new \RuntimeException('Freigabe fehlt');Ordering::menuPublication($s,$uid,$r['enabled']);$s['revision']++;break;
                    case 'publication':if(!is_bool($r['enabled']??null))throw new \RuntimeException('Freigabe fehlt');Ordering::publication($s,$uid,$r['enabled']);$s['revision']++;break;
                    case 'topup-test':if(!is_int($r['cents']??null))throw new \RuntimeException('Betrag erforderlich');$result=TestWallet::topup($store['ledger'],$uid,$r['cents'],(string)($r['key']??''));break;
                    case 'place':$before=count($s['orders']);$result=Ordering::place($s,$uid,$r);$new=count($s['orders'])>$before;if($new)Presence::requireOrder($presence);if($new||($result['walletFunded']??false)){TestWallet::reserve($store['ledger'],$uid,$rid.':'.$result['id'],$result['totalCents']);if($new){$s['orders'][$before]['walletFunded']=true;$result['walletFunded']=true;}}break;
                    case 'transition':$result=Ordering::transition($s,$uid,(string)($r['id']??''),(string)($r['target']??''));$settlement=['delivered'=>'captured','cancelled'=>'released','refunded'=>'refunded'][$result['status']]??null;if($settlement)TestWallet::settle($store['ledger'],$result['guest'],$rid.':'.$result['id'],$settlement);break;
                    case 'message':Ordering::message($s,$uid,(string)($r['id']??''),(string)($r['text']??''));break;
                    default:throw new \RuntimeException('Aktion nicht verfügbar');
                }
                return $result;
            });
            return GDT_JSON::make()->value($result);
        } catch(\mysqli_sql_exception $e) {Application::setResponseCode(503);return GDT_JSON::make()->value(['error'=>'Datenbankvorgang fehlgeschlagen; bitte später erneut versuchen']);
        } catch(\Throwable $e) {Application::setResponseCode(in_array($e->getCode(),[403,405,409,503],true)?$e->getCode():400);return GDT_JSON::make()->value(['error'=>$e instanceof \RuntimeException||$e instanceof \InvalidArgumentException?$e->getMessage():'Anfrage konnte nicht verarbeitet werden']);}
    }
}
