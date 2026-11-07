<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use GDO\Core\Application;
use GDO\Core\GDT;
use GDO\Core\GDT_JSON;
use GDO\Core\MethodAjax;
use GDO\DB\Database;
use GDO\LinkUUp\Commerce\Access;
use GDO\LinkUUp\LUP_Room;
use GDO\Session\GDO_Session;
use GDO\User\GDO_User;

/** Minimal owner navigation for the explicitly enabled, local Gastro pilot. */
final class GastroVenues extends MethodAjax
{
    public function hasPermission(GDO_User $user, string &$error, array &$args): bool
    {
        $allowed = GastroLocal::pilotEnabled()
            && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
            && GastroLocal::localAppRequest()
            && in_array($_SERVER['HTTP_ORIGIN'] ?? '', ['', Access::origin()], true)
            && $user->isMember() && $user->gdoVar('user_deleted') === null
            && GDO_Session::instance();
        if (!$allowed) $error = 'err_not_allowed';
        return (bool)$allowed;
    }

    public function execute(): GDT
    {
        header('Cache-Control: no-store');
        try {
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
                throw new \RuntimeException('Methode nicht erlaubt', 405);
            }
            $user = GDO_User::current();
            $error = ''; $args = [];
            if (!$this->hasPermission($user, $error, $args)) {
                throw new \RuntimeException('Zugriff nicht verfügbar', 403);
            }
            $session = GDO_Session::instance();
            if (!$session) throw new \RuntimeException('Anmeldung erforderlich', 403);
            $token = $session->getToken();
            $auth = $session->gdoVar('sess_auth');
            if (!is_string($token) || $token === '' || !is_string($auth)
                || !preg_match('/^[a-f0-9]{32}$/D', $auth)) {
                throw new \RuntimeException('Sitzung neu öffnen', 403);
            }
            $db = Database::instance()->getLink();
            if (!($db instanceof \mysqli)) throw new \RuntimeException('MariaDB-Pilot erforderlich', 503);
            $tables = [GDO_Session::table()->gdoTableName(), GDO_User::table()->gdoTableName(), LUP_Room::table()->gdoTableName()];
            foreach ($tables as $table) {
                if (!preg_match('/^[a-zA-Z0-9_]+$/D', $table)) throw new \RuntimeException('Ungültiges Speicherschema', 503);
            }
            // One uncached statement checks current identity and ownership together.
            // LEFT JOIN retains the session row for a valid member with no venues.
            // No browser-selected owner and no staff override are accepted.
            $sql = 'SELECT s.sess_token,s.sess_auth,s.sess_time,u.user_type,u.user_deleted,'
                . 'r.room_id,r.room_name,r.room_enabled FROM `'.$tables[0].'` s '
                . 'JOIN `'.$tables[1].'` u ON u.user_id=s.sess_user '
                . 'LEFT JOIN `'.$tables[2].'` r ON r.room_owner=u.user_id AND r.room_deleted IS NULL '
                . 'WHERE s.sess_id=? AND s.sess_user=? ORDER BY r.room_name,r.room_id';
            $stmt = $db->prepare($sql);
            $sid = (string)$session->getID(); $uid = (string)$user->getID();
            $stmt->bind_param('ss', $sid, $uid);
            $stmt->execute();
            $rows = $stmt->get_result();
            $first = $rows->fetch_assoc();
            if (!$first || $first['user_type'] !== 'member' || $first['user_deleted'] !== null
                || !hash_equals((string)$first['sess_token'], $token)
                || !hash_equals((string)$first['sess_auth'], $auth)) {
                throw new \RuntimeException('Sitzung abgelaufen', 403);
            }
            $last = strtotime(($first['sess_time'] ?? '').' UTC');
            if ($last === false || (GDO_SESS_TIME > 0 && $last < time() - GDO_SESS_TIME)) {
                throw new \RuntimeException('Sitzung abgelaufen', 403);
            }
            $venues = [];
            do {
                if ($first['room_id'] !== null && \GDO\LinkUUp\Commerce\OperatorApplications::approvalFor($uid,(string)$first['room_id'])) {
                    $venues[] = ['id' => (string)$first['room_id'], 'name' => (string)$first['room_name'],
                        'enabled' => (string)$first['room_enabled'] === '1'];
                }
            } while ($first = $rows->fetch_assoc());
            $stmt->close();
            return GDT_JSON::make()->value(['venues' => $venues]);
        } catch (\Throwable $e) {
            $code = in_array($e->getCode(), [403, 405, 503], true) ? $e->getCode() : 503;
            Application::setResponseCode($code);
            // SQL details and unexpected exception text never enter the response.
            $message = $e instanceof \RuntimeException && !($e instanceof \mysqli_sql_exception)
                ? $e->getMessage() : 'Locationübersicht konnte nicht geladen werden';
            return GDT_JSON::make()->value(['error' => $message]);
        }
    }
}
