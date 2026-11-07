<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Request policy. Identity and ownership must come from the server session/DB. */
final class Access
{
    public static function origin(): string
    {
        $origin=defined('GDO_LINKUUP_GASTRO_ORIGIN')?constant('GDO_LINKUUP_GASTRO_ORIGIN'):'http://app.localhost';
        if (!is_string($origin) || !preg_match('~^http://(?:app\.localhost|localhost|127\.0\.0\.1)(?::([1-9][0-9]{0,4}))?$~D',$origin,$m)
            || (isset($m[1]) && (int)$m[1]>65535)) throw new \RuntimeException('Ungültige lokale Pilot-Adresse',503);
        return $origin;
    }
    public static function check(bool $activeMember, bool $owner, bool $inactiveRoom, string $action, string $method, string $origin, string $token, string $supplied): void
    {
        if (!$activeMember) throw new \RuntimeException('Anmeldung erforderlich', 403);
        $actions = ['state','preview-import','save-menu','save-tables','publication','transition','topup-test','place','message','mfa-begin','mfa-confirm','mfa-verify','mfa-recover'];
        if (!in_array($action, $actions, true)) throw new \RuntimeException('Aktion nicht verfügbar', 400);
        if (!in_array($method, ['GET','POST'], true)) throw new \RuntimeException('Methode nicht erlaubt', 405);
        if ($action !== 'state' && ($method !== 'POST' || $origin !== self::origin() || strlen($token) !== 64 || !hash_equals($token, $supplied))) {
            throw new \RuntimeException('Sitzung abgelaufen. Bitte neu öffnen.', 403);
        }
        if (in_array($action, ['preview-import','save-menu','save-tables','publication','transition','mfa-begin','mfa-confirm','mfa-verify','mfa-recover'], true) && !$owner) {
            throw new \RuntimeException('Keine Berechtigung für diese Location', 403);
        }
        // Existing orders remain readable and settleable by their authorized parties.
        if ($inactiveRoom && in_array($action, ['preview-import','save-menu','save-tables','publication','place','topup-test'], true)) {
            throw new \RuntimeException('Location ist nicht verfügbar', 409);
        }
    }
}
