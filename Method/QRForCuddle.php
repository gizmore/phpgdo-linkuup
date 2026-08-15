<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\LinkUUp\LUP_CuddleToken;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Net\GDT_Url;
use GDO\QRCode\Method\Render;
use GDO\User\GDO_User;

/** Render a one-time Cuddle QR code for the currently authenticated user. */
final class QRForCuddle extends \GDO\Core\Method
{
	public function isUserRequired(): bool { return true; }

    public function gdoParameters(): array
    {
        return [GDT_Object::make('room')->table(LUP_Room::table())->notNull()];
    }

    public function execute(): GDT
    {
        $room = $this->gdoParameterValue('room');
        if ($room->isDisabled() || !$room->gdoValue('room_active'))
        {
            return $this->error('err_lup_cuddle_room');
        }
        $token = LUP_CuddleToken::issue(GDO_User::current(), $room, Module_LinkUUp::instance()->cfgCuddleTokenTTL());
        $data = GDT_Url::absolute(href('LinkUUp', 'Cuddle', sprintf('&token=%s&mac=%s', $token->gdoVar('ctoken_token'), $token->signature())));
        return (new Render())->inputs(['data' => $data, 'size' => '1024'])->execute();
    }

    public function isAlwaysTransactional(): bool { return true; }
}
