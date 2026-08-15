<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_String;
use GDO\Core\Method;
use GDO\LinkUUp\LUP_Cuddle;
use GDO\LinkUUp\LUP_CuddleToken;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;

/** Redeem one short-lived QR Cuddle token. */
final class Cuddle extends Method
{
	public function isUserRequired(): bool { return true; }

    public function gdoParameters(): array
    {
        return [
            GDT_String::make('token')->ascii()->length(32)->notNull(),
            GDT_String::make('mac')->ascii()->length(64)->notNull(),
        ];
    }

    public function isAlwaysTransactional(): bool { return true; }

    public function execute(): GDT
    {
        $scanner = GDO_User::current();
        $token = LUP_CuddleToken::find($this->gdoParameterVar('token'));
        if (!$token || !hash_equals($token->signature(), $this->gdoParameterVar('mac')))
        {
            return $this->error('err_lup_cuddle_token');
        }
        if ($token->isUsed())
        {
            return $this->error('err_lup_cuddle_used');
        }
        if ($token->isExpired())
        {
            return $this->error('err_lup_cuddle_expired');
        }

        $issuer = $token->issuer();
        $room = $token->room();
        if ($room->isDisabled() || !$room->gdoValue('room_active'))
        {
            return $this->error('err_lup_cuddle_room');
        }
        if ($issuer->getID() === $scanner->getID())
        {
            return $this->error('err_lup_cuddle_self');
        }
        if (LUP_Cuddle::exists($issuer, $scanner, $room))
        {
            return $this->error('err_lup_already_cuddled');
        }

        LUP_Cuddle::create($issuer, $scanner, $room);
        $token->consume($scanner);
        $module = Module_LinkUUp::instance();
        $module->saveUserSetting($issuer, 'lup_cuddles', (string)($module->cfgCuddles($issuer) + 1));
        $module->saveUserSetting($scanner, 'lup_cuddles', (string)($module->cfgCuddles($scanner) + 1));

        return $this->message('msg_lup_cuddle_success');
    }
}
