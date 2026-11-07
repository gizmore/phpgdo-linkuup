<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** MariaDB/InnoDB pilot store. Never migrates tables or imports old JSON on request. */
final class TransactionStore
{
    private const CSRF_TTL = 3600;
    private const CSRF_MAX_ENTRIES = 512;
    public static function run(\mysqli $db, array $tables, array $identity, bool $write, callable $operation): array
    {
        $begun=false;
        try {
            foreach (['session','user','room','store'] as $name) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/D', $tables[$name]??'')) throw new \RuntimeException('Ungültiges Speicherschema');
                $stmt=$db->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
                $stmt->bind_param('s',$tables[$name]);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();
                if (strtoupper($row['ENGINE']??'')!=='INNODB') throw new \RuntimeException('Transaktionaler Gastro-Speicher ist nicht eingerichtet',503);
            }
            // Do not implicitly commit a transaction owned by another GDO caller.
            if ((int)$db->query('SELECT @@in_transaction')->fetch_row()[0]!==0) throw new \RuntimeException('Verschachtelte Transaktion nicht erlaubt',503);
            $db->begin_transaction();$begun=true;
            // Fixed lock order: session -> users (requester + owner, numeric order) -> room -> shared pilot ledger -> approval.
            $session=self::row($db,$tables['session'],'sess_id',(string)$identity['session']);
            if (!$session || empty($identity['token']) || (string)$session['sess_user']!==(string)$identity['user']
                || !hash_equals((string)$session['sess_token'],(string)$identity['token'])) throw new \RuntimeException('Sitzung abgelaufen',403);
            if (($identity['maxAge']??0)>0) {
                $last=strtotime(($session['sess_time']??'').' UTC');
                if ($last===false || $last<time()-(int)$identity['maxAge']) throw new \RuntimeException('Sitzung abgelaufen',403);
            }
            if (!is_string($session['sess_auth']??null) || !preg_match('/^[a-f0-9]{32}$/D',$session['sess_auth'])) throw new \RuntimeException('Session-Authentifizierungsschema fehlt',503);
            // This hint is not authority. Ownership is revalidated under the room lock.
            // Lock the owner before the room: approvalFor() also checks that user,
            // otherwise a guest can hold room/store while waiting for an owner
            // who already holds their user row and is waiting for the same room.
            $hint=self::row($db,$tables['room'],'room_id',(string)$identity['room'],false);
            if (!$hint) throw new \RuntimeException('Zugriff nicht verfügbar',403);
            $ownerHint=isset($hint['room_owner'])?(string)$hint['room_owner']:null;
            $userIds=[(string)$identity['user']];
            if ($ownerHint!==null) {
                if (!ctype_digit($ownerHint)) throw new \RuntimeException('Ungültige Locationzuordnung',503);
                $userIds[]=$ownerHint;
            }
            $userIds=array_values(array_unique($userIds));
            usort($userIds,static fn(string $a,string $b):int=>strlen($a)<=>strlen($b) ?: strcmp($a,$b));
            $lockedUsers=[];
            foreach($userIds as $userId)$lockedUsers[$userId]=self::row($db,$tables['user'],'user_id',$userId);
            $user=$lockedUsers[(string)$identity['user']];
            $room=self::row($db,$tables['room'],'room_id',(string)$identity['room']);
            if ($room && (isset($room['room_owner'])?(string)$room['room_owner']:null)!==$ownerHint)
                throw new \RuntimeException('Locationzuordnung geändert. Bitte erneut öffnen.',409);
            if (!$user || $user['user_type']!=='member' || $user['user_deleted']!==null || !$room) throw new \RuntimeException('Zugriff nicht verfügbar',403);
            $record=self::row($db,$tables['store'],'store_id','1');
            if (!$record) throw new \RuntimeException('Gastro-Speicher ist nicht initialisiert',503);
            $store=json_decode($record['store_data'],true,64,JSON_THROW_ON_ERROR);
            if (!is_array($store['rooms']??null)||!is_array($store['ledger']??null)) throw new \RuntimeException('Ungültiger Speicher');
            // Never write sess_data: GDO may hold a dirty, older Session object.
            // Bind the independent CSRF token to the verified session/user/token.
            $csrfKey=hash('sha256',json_encode([(string)$identity['session'],(string)$identity['user'],(string)$identity['token'],$session['sess_auth']],JSON_THROW_ON_ERROR));
            $csrfChanged=false;$now=time();
            $store['csrf']??=[];
            if (!is_array($store['csrf'])) throw new \RuntimeException('Ungültiger Speicher',503);
            // Fixed lifetime (not extended by polling); remove expired/legacy data.
            foreach ($store['csrf'] as $key=>$entry) {
                if (!is_array($entry) || !is_int($entry['expires']??null)
                    || $entry['expires']<=$now || $entry['expires']>$now+self::CSRF_TTL
                    || !is_string($entry['token']??null) || !preg_match('/^[a-f0-9]{64}$/D',$entry['token'])) {
                    unset($store['csrf'][$key]);$csrfChanged=true;
                }
            }
            $csrf=$store['csrf'][$csrfKey]['token']??null;
            if ($csrf===null) {
                if (empty($identity['allowCsrfInit'])) throw new \RuntimeException('Sitzung neu öffnen',403);
                if (count($store['csrf'])>=self::CSRF_MAX_ENTRIES) throw new \RuntimeException('Pilot-Sitzungskapazität erreicht; später erneut versuchen',503);
                $maxAge=(int)($identity['maxAge']??0);$ttl=$maxAge>0?min(self::CSRF_TTL,$maxAge):self::CSRF_TTL;
                $csrf=bin2hex(random_bytes(32));$store['csrf'][$csrfKey]=['token'=>$csrf,'expires'=>$now+$ttl];$csrfChanged=true;
            }
            if (!empty($identity['requireCsrf']) && !hash_equals($csrf,(string)($identity['csrf']??''))) throw new \RuntimeException('Sitzung geändert',403);
            $result=$operation($store,['user'=>$user,'room'=>$room,'csrf'=>$csrf,'binding'=>$csrfKey]);
            if ($write || $csrfChanged) {
                $json=json_encode($store,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
                if (strlen($json)>16000000) throw new \RuntimeException('Pilot-Speichergrenze erreicht',503);
                $stmt=$db->prepare('UPDATE `'.$tables['store'].'` SET store_data=? WHERE store_id=1');
                $stmt->bind_param('s',$json);$stmt->execute();$stmt->close();
            }
            if (!$db->commit()) throw new \RuntimeException('Buchung nicht bestätigt',503);
            $begun=false;return $result;
        } catch (\mysqli_sql_exception $e) {
            // No SQL or request values in client-visible error messages.
            throw new \RuntimeException('Datenbankvorgang fehlgeschlagen; Stand vor erneutem Versuch prüfen',503);
        } finally {if ($begun)$db->rollback();}
    }
    private static function row(\mysqli $db,string $table,string $key,string $id,bool $lock=true): ?array
    {
        $stmt=$db->prepare('SELECT * FROM `'.$table.'` WHERE `'.$key.'`=?'.($lock?' FOR UPDATE':''));
        $stmt->bind_param('s',$id);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();$stmt->close();return $row;
    }
}
