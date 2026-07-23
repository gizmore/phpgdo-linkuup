<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\Method;
use GDO\LinkUUp\LUP_Room;
use GDO\User\GDO_User;

/**
 * Show a list of all your coworkers.
 *
 * @author gizmore
 */
final class Coworkers extends Method
{

	public function execute(): GDT
	{
		$user = GDO_User::current();
		$tVars = [
			'rooms' => LUP_Room::getEditableRooms($user),
		];
		return $this->templatePHP('page/coworkers.php', $tVars);
	}

}
