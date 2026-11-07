<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;
use GDO\DB\Database;
use GDO\LinkUUp\LUP_OperatorApplication;

/** All transitions are server-owned, with fixed InnoDB locking and no implicit ownership grant. */
final class OperatorApplications
{
    private static function db(): \mysqli
    {
        $db=Database::instance()->getLink();
        if(!($db instanceof \mysqli))throw new \RuntimeException('MariaDB erforderlich',503);
        return $db;
    }
    private static function run(\mysqli $db,string $sql,array $args=[]): \mysqli_stmt
    {
        $s=$db->prepare($sql);if($args)$s->bind_param(str_repeat('s',count($args)),...$args);$s->execute();return $s;
    }
    private static function row(\mysqli $db,string $sql,array $args=[]): ?array
    { $s=self::run($db,$sql,$args);$r=$s->get_result()->fetch_assoc();$s->close();return $r; }
    private static function begin(\mysqli $db,bool $review): void
    {
        if((int)self::row($db,'SELECT @@in_transaction AS active')['active'])throw new \RuntimeException('Verschachtelte Transaktion abgelehnt',503);
        $tables=['lup_operatorapplication','gdo_user','lup_room'];
        if($review)$tables=array_merge($tables,['gdo_userpermission','gdo_permission']);
        foreach($tables as $t){$r=self::row($db,'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$t]);if(($r['ENGINE']??'')!=='InnoDB')throw new \RuntimeException('Antragsprüfung benötigt InnoDB: '.$t,503);}
        $db->begin_transaction();
    }
    private static function member(?array $u): void
    { if(!$u||$u['user_type']!=='member'||$u['user_deleted']!==null)throw new \RuntimeException('Aktives Mitglied erforderlich',403); }
    public static function normalize(array $in): array
    {
        $out=[];
        foreach(['business'=>160,'kind'=>100,'address'=>300,'contact'=>160,'email'=>254,'phone'=>80,'details'=>2000] as $key=>$max){
            if(!is_string($in[$key]??null))throw new \RuntimeException('Unvollständige Antragsdaten',400);
            $v=trim($in[$key]);if(($v===''&&$key!=='phone')||mb_strlen($v)>$max||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$v))throw new \RuntimeException('Ungültiges Feld: '.$key,400);$out[$key]=$v;
        }
        if(!filter_var($out['email'],FILTER_VALIDATE_EMAIL))throw new \RuntimeException('Gültige E-Mail erforderlich',400);
        return $out;
    }
    public static function submit(string $uid,?string $rid,array $input): string
    {
        if(!ctype_digit($uid)||($rid!==null&&!ctype_digit($rid)))throw new \RuntimeException('Ungültige Zuordnung',400);
        $v=self::normalize($input);$db=self::db();self::begin($db,false);
        try{
            self::member(self::row($db,'SELECT user_type,user_deleted FROM gdo_user WHERE user_id=? FOR UPDATE',[$uid]));
            if($rid!==null){$room=self::row($db,'SELECT room_owner,room_deleted FROM lup_room WHERE room_id=? FOR UPDATE',[$rid]);if(!$room||(string)$room['room_owner']!==$uid||$room['room_deleted']!==null)throw new \RuntimeException('Nur eigene Locations können zugeordnet werden',403);}
            $pending=self::row($db,"SELECT oa_id FROM lup_operatorapplication WHERE oa_user=? AND oa_status IN ('pending','approved') AND oa_room <=> ? FOR UPDATE",[$uid,$rid]);
            if($pending)throw new \RuntimeException('Für diese Zuordnung wird bereits ein Antrag geprüft',409);
            self::run($db,'INSERT INTO lup_operatorapplication (oa_user,oa_room,oa_business,oa_kind,oa_address,oa_contact,oa_email,oa_phone,oa_details,oa_status,oa_version,oa_created,oa_history) VALUES (?,?,?,?,?,?,?,?,?,\'pending\',1,UTC_TIMESTAMP(),\'[]\')',[$uid,$rid,...array_values($v)])->close();
            $id=(string)$db->insert_id;$db->commit();return $id;
        }catch(\Throwable $e){$db->rollback();throw $e;}
    }
    public static function decision(string $actor,string $id,int $version,string $target,string $note,bool $confirmed): void
    {
        if(!ctype_digit($actor)||!ctype_digit($id)||!in_array($target,['approved','rejected','revoked'],true)||$version<1||trim($note)===''||mb_strlen($note)>2000)throw new \RuntimeException('Ungültige Prüfentscheidung',400);
        if($target==='approved'&&!$confirmed)throw new \RuntimeException('Prüfung und Vertragsklärung müssen ausdrücklich bestätigt sein',400);
        $db=self::db();self::begin($db,true);
        try{
            // Lock all relevant users in numeric order, then permission, room, application.
            $initial=self::row($db,'SELECT oa_user,oa_room FROM lup_operatorapplication WHERE oa_id=?',[$id]);if(!$initial)throw new \RuntimeException('Antrag nicht gefunden',404);
            if((string)$initial['oa_user']===$actor)throw new \RuntimeException('Eigene Anträge dürfen nicht selbst entschieden werden',403);
            $uids=[$actor,(string)$initial['oa_user']];usort($uids,fn($a,$b)=>(int)$a<=>(int)$b);
            foreach($uids as $uid){$locked=self::row($db,'SELECT user_type,user_deleted FROM gdo_user WHERE user_id=? FOR UPDATE',[$uid]);if($uid===$actor||$target==='approved')self::member($locked);}
            $admin=self::row($db,"SELECT up.perm_user_id FROM gdo_userpermission up JOIN gdo_permission p ON p.perm_id=up.perm_perm_id WHERE up.perm_user_id=? AND p.perm_name='admin' FOR UPDATE",[$actor]);if(!$admin)throw new \RuntimeException('Administratorrecht erforderlich',403);
            $room=$initial['oa_room']===null?null:self::row($db,'SELECT room_owner,room_deleted FROM lup_room WHERE room_id=? FOR UPDATE',[(string)$initial['oa_room']]);
            $app=self::row($db,'SELECT * FROM lup_operatorapplication WHERE oa_id=? FOR UPDATE',[$id]);
            if((int)$app['oa_version']!==$version)throw new \RuntimeException('Antrag wurde bereits geändert. Neu laden.',409);
            if(($target==='revoked'&&$app['oa_status']!=='approved')||($target!=='revoked'&&$app['oa_status']!=='pending'))throw new \RuntimeException('Dieser Statuswechsel ist nicht erlaubt',409);
            if($target==='approved'&&(!$room||(string)$room['room_owner']!==(string)$app['oa_user']||$room['room_deleted']!==null))throw new \RuntimeException('Vor Freischaltung muss die eigene Location geprüft und zugeordnet sein',409);
            $history=json_decode($app['oa_history'],true,flags:JSON_THROW_ON_ERROR);$history[]=['from'=>$app['oa_status'],'to'=>$target,'actor'=>$actor,'at'=>gmdate('c'),'note'=>$note,'manual_contract_confirmation'=>$target==='approved'&&$confirmed];$encoded=json_encode($history,JSON_THROW_ON_ERROR);if(strlen($encoded)>60000)throw new \RuntimeException('Prüfhistorie zu groß',409);
            self::run($db,'UPDATE lup_operatorapplication SET oa_status=?,oa_version=oa_version+1,oa_reviewer=?,oa_reviewed=UTC_TIMESTAMP(),oa_review_note=?,oa_history=? WHERE oa_id=?',[$target,$actor,$note,$encoded,$id])->close();$db->commit();
        }catch(\Throwable $e){$db->rollback();throw $e;}
    }
    /** Fresh, non-cached read. An enclosing InnoDB transaction also locks the approval rows. */
    public static function approvalFor(string $uid,string $rid): bool
    {
        if(!ctype_digit($uid)||!ctype_digit($rid))return false;
        $db=self::db();
        $engine=self::row($db,'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',['lup_operatorapplication']);
        if(($engine['ENGINE']??'')!=='InnoDB')return false;
        $lock=((int)self::row($db,'SELECT @@in_transaction AS active')['active'])?' FOR UPDATE':'';
        $r=self::row($db,"SELECT a.oa_id FROM lup_operatorapplication a JOIN gdo_user u ON u.user_id=a.oa_user JOIN lup_room r ON r.room_id=a.oa_room WHERE a.oa_user=? AND a.oa_room=? AND a.oa_status='approved' AND u.user_type='member' AND u.user_deleted IS NULL AND r.room_owner=a.oa_user AND r.room_deleted IS NULL ORDER BY a.oa_id DESC LIMIT 1".$lock,[$uid,$rid]);return $r!==null;
    }
    /** Applicant sees only their own records; reviewers must use the admin-protected method. */
    public static function applications(string $uid,bool $admin=false): array
    {
        if(!ctype_digit($uid))return [];$db=self::db();
        if($admin){$a=self::row($db,"SELECT u.user_id FROM gdo_user u JOIN gdo_userpermission up ON up.perm_user_id=u.user_id JOIN gdo_permission p ON p.perm_id=up.perm_perm_id WHERE u.user_id=? AND u.user_type='member' AND u.user_deleted IS NULL AND p.perm_name='admin'",[$uid]);if(!$a)throw new \RuntimeException('Administratorrecht erforderlich',403);}
        $s=self::run($db,'SELECT * FROM lup_operatorapplication'.($admin?'':' WHERE oa_user=?').' ORDER BY oa_id DESC LIMIT 100',$admin?[]:[$uid]);$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);$s->close();return $rows;
    }
}
