<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;
use GDO\Core\{Application,GDT,GDT_JSON,GDT_Object,MethodAjax};
use GDO\DB\Database;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Commerce\{Access,PublicMenu};
use GDO\User\GDO_User;
/** Anonymous read-only card projection; the pilot/network gate still applies. */
final class GastroMenu extends MethodAjax
{
    public function gdoParameters(): array {return [GDT_Object::make('room')->table(LUP_Room::table())->notNull()];}
    public function hasPermission(GDO_User $user,string &$error,array &$args): bool
    {
        $ok=GastroLocal::pilotEnabled() && in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)
            && GastroLocal::localAppRequest() && in_array($_SERVER['HTTP_ORIGIN']??'',['',Access::origin()],true);
        if(!$ok)$error='err_not_allowed';return $ok;
    }
    public function execute(): GDT
    {
        header('Cache-Control: no-store');
        try {
            $error='';$args=[];
            if(!$this->hasPermission(GDO_User::current(),$error,$args))throw new \RuntimeException('Karte nicht verfügbar',403);
            if(($_SERVER['REQUEST_METHOD']??'')!=='GET')throw new \RuntimeException('Nur Kartenansicht erlaubt',405);
            $rid=(string)$this->gdoParameterValue('room')->getID();
            $db=Database::instance()->getLink();
            if(!($db instanceof \mysqli))throw new \RuntimeException('Karte momentan nicht verfügbar',503);
            // One statement: current owner/status/approval and menu from one read snapshot.
            $stmt=$db->prepare("SELECT r.room_name,s.store_data,(r.room_enabled=1 AND r.room_deleted IS NULL AND u.user_type='member' AND u.user_deleted IS NULL AND EXISTS(SELECT 1 FROM lup_operatorapplication a WHERE a.oa_room=r.room_id AND a.oa_user=r.room_owner AND a.oa_status='approved')) AS eligible FROM lup_room r LEFT JOIN gdo_user u ON u.user_id=r.room_owner LEFT JOIN lup_gastro_store s ON s.store_id=1 WHERE r.room_id=?");
            $stmt->bind_param('s',$rid);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
            if(!$row)throw new \RuntimeException('Karte nicht verfügbar',403);
            $store=json_decode($row['store_data']??'{"rooms":{}}',true,64,JSON_THROW_ON_ERROR);
            return GDT_JSON::make()->value(PublicMenu::project($rid,$row['room_name'],$store['rooms'][$rid]??null,(string)$row['eligible']==='1'));
        }catch(\Throwable $e){Application::setResponseCode(in_array($e->getCode(),[403,405],true)?$e->getCode():503);return GDT_JSON::make()->value(['error'=>'Karte momentan nicht verfügbar']);}
    }
}
