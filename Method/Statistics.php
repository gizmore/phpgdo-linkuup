<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\Method;
use GDO\LinkUUp\LUP_Room;
use GDO\User\GDO_User;

/**
 * Statistics overview for room owners and coworkers.
 *
 * @author gizmore
 */
final class Statistics extends Method
{

	public function execute(): GDT
	{
		$user = GDO_User::current();
		$rooms = LUP_Room::getEditableRooms($user);
		$tVars = [
			'rooms' => $rooms,
		];
		return $this->templatePHP('page/statistics.php', $tVars);
	}

}
