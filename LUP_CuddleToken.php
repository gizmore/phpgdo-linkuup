<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Char;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Object;
use GDO\Date\GDT_DateTime;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/** A short-lived, one-time QR bearer token. */
final class LUP_CuddleToken extends GDO
{
    public function gdoColumns(): array
    {
        return [
            GDT_AutoInc::make('ctoken_id'),
            GDT_Char::make('ctoken_token')->ascii()->length(32)->notNull()->unique(),
            GDT_User::make('ctoken_issuer')->notNull(),
            GDT_Object::make('ctoken_room')->table(LUP_Room::table())->notNull(),
            GDT_DateTime::make('ctoken_expires')->notNull(),
            GDT_DateTime::make('ctoken_used_at'),
            GDT_User::make('ctoken_used_by')->cascadeNull(),
            GDT_CreatedAt::make('ctoken_created'),
        ];
    }

    public static function issue(GDO_User $issuer, LUP_Room $room, int $ttl): self
    {
        return self::blank([
            'ctoken_token' => bin2hex(random_bytes(16)),
            'ctoken_issuer' => $issuer->getID(),
            'ctoken_room' => $room->getID(),
            'ctoken_expires' => gmdate('Y-m-d H:i:s', time() + $ttl),
        ])->insert();
    }

    public static function findToken(string $token): ?self
    {
        return self::getByVars(['ctoken_token' => $token]);
    }

    public function signature(): string
    {
        return hash_hmac('sha256', implode('|', [
            'lup-cuddle-v1',
            $this->gdoVar('ctoken_token'),
            $this->gdoVar('ctoken_issuer'),
            $this->gdoVar('ctoken_room'),
            $this->gdoVar('ctoken_expires'),
        ]), GDO_SALT . '|LinkUUp:Cuddle');
    }

    public function isUsed(): bool { return $this->gdoVar('ctoken_used_at') !== null; }

    public function isExpired(): bool { return strtotime($this->gdoVar('ctoken_expires') . ' UTC') <= time(); }

    public function issuer(): GDO_User { return $this->gdoValue('ctoken_issuer'); }

    public function room(): LUP_Room { return $this->gdoValue('ctoken_room'); }

    public function consume(GDO_User $user): void
    {
        $this->saveVars([
            'ctoken_used_at' => gmdate('Y-m-d H:i:s'),
            'ctoken_used_by' => $user->getID(),
        ]);
    }
}
