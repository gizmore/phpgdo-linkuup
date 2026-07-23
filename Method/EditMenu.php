<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\Method;
use GDO\LinkUUp\GDT_RoomSelect;
use GDO\LinkUUp\LUP_Room;

/**
 * ???
 *
 * @author gizmore
 */
final class EditMenu extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_RoomSelect::make('room')->notNull(),
		];
	}

	public function execute(): GDT
	{
		$room = $this->getRoom();
		$tVars = [
			'room' => $room,
		];
		return $this->templatePHP('crumb/edit_menu.php', $tVars);
	}

	public function getRoom(): LUP_Room
	{
		return $this->gdoParameterValue('room');
	}

}
