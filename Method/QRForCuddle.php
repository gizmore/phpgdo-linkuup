<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\LinkUUp\LUP_CuddleToken;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Net\GDT_Url;
use GDO\QRCode\Method\Render;
use GDO\User\GDO_User;

/** Render a one-time Cuddle QR code for the currently authenticated user. */
final class QRForCuddle extends \GDO\Core\Method
{
	public function isUserRequired(): bool { return true; }

    public function execute(): GDT
    {
        $token = LUP_CuddleToken::issue(GDO_User::current(), Module_LinkUUp::instance()->cfgCuddleTokenTTL());
        $data = GDT_Url::absolute(href('LinkUUp', 'Cuddle', sprintf('&token=%s&mac=%s', $token->gdoVar('ctoken_token'), $token->signature())));
        return (new Render())->inputs(['data' => $data, 'size' => '1024'])->execute();
    }

    public function isAlwaysTransactional(): bool { return true; }
}
