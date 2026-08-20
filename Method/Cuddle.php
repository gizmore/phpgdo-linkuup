<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Char;
use GDO\Core\GDT_Hook;
use GDO\Core\Method;
use GDO\LinkUUp\LUP_Cuddle;
use GDO\LinkUUp\LUP_CuddleToken;
use GDO\LinkUUp\LUP_SignupGPS;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Maps\Position;
use GDO\User\GDO_User;

/** Redeem one short-lived QR Cuddle token. */
final class Cuddle extends Method
{
	public function isUserRequired(): bool { return true; }

    public function gdoParameters(): array
    {
        return [
            GDT_Char::make('token')->ascii()->length(32)->notNull(),
            GDT_Char::make('mac')->ascii()->length(64)->notNull(),
        ];
    }

    public function isAlwaysTransactional(): bool { return true; }

    public function execute(): GDT
    {
        $scanner = GDO_User::current();
        $token = LUP_CuddleToken::findToken($this->gdoParameterVar('token'));
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
        if ($issuer->getID() === $scanner->getID())
        {
            return $this->error('err_lup_cuddle_self');
        }
		if (!$issuerGPS = LUP_SignupGPS::getById($issuer->getID()))
		{
			return $this->error('err_lup_cuddle_position');
		}
		if (!$scannerGPS = LUP_SignupGPS::getById($scanner->getID()))
		{
			return $this->error('err_lup_cuddle_position');
		}
		$issuerPosition = $issuerGPS->gdoValue('lsp_pos');
		$scannerPosition = $scannerGPS->gdoValue('lsp_pos');
		$distance = Position::distanceCalculation(
			$issuerPosition->getLat(), $issuerPosition->getLng(),
			$scannerPosition->getLat(), $scannerPosition->getLng(),
			'km');
		if ($distance >= ($moduleRange = Module_LinkUUp::instance()->cfgCuddleRange()))
		{
			return $this->error('err_lup_cuddle_distance', [(int)round($moduleRange * 1000)]);
		}
        if (LUP_Cuddle::exists($issuer, $scanner))
        {
            return $this->error('err_lup_already_cuddled');
        }

        LUP_Cuddle::create($issuer, $scanner);
        $token->consume($scanner);
        $module = Module_LinkUUp::instance();
        $module->saveUserSetting($issuer, 'lup_cuddles', (string)($module->cfgCuddles($issuer) + 1));
        $module->saveUserSetting($scanner, 'lup_cuddles', (string)($module->cfgCuddles($scanner) + 1));
        GDT_Hook::callWithIPC('LUPUserRecache', $issuer->getID(), $scanner->getID());

        return $this->message('msg_lup_cuddle_success');
    }
}
