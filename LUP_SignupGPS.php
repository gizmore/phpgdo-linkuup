<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Maps\GDT_Position;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * Record last user GPS.
 */
final class LUP_SignupGPS extends GDO
{

	###########
	### GDO ###
	###########
	public static function updateGPS(GDO_User $user, $lat, $lng)
	{
		return self::blank([
			'lsp_user' => $user->getID(),
			'lsp_pos_lat' => $lat,
			'lsp_pos_lng' => $lng,
		])->replace();
	}

	public function gdoCached(): bool { return false; }

	###########
	### API ###
	###########

	public function gdoColumns(): array
	{
		return [
			GDT_User::make('lsp_user')->primary(),
			GDT_Position::make('lsp_pos')->notNull(),
		];
	}


}
