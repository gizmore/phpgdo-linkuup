<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\Method;
use GDO\File\GDO_File;
use GDO\File\Method\GetFile;
use GDO\LinkUUp\GDT_RoomSelect;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Module_LinkUUp;

/**
 * Get the icon for a room.
 *
 * @version 7.0.1
 * @author gizmore
 */
final class RoomIcon extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_RoomSelect::make('id'),
		];
	}

	public function execute(): GDT
	{
		$room = $this->getRoom();
		if (!($icon = $room->getIcon()))
		{
			hdr('Content-Type: image/jpeg');
			die(Module_LinkUUp::instance()->templateFile('img/default_room_icon.jpg'));
		}
		return $this->renderIcon($icon, GetFile::make());
	}

	private function getRoom(): LUP_Room
	{
		return $this->gdoParameterValue('id');
	}

	private function renderIcon(GDO_File $file, GetFile $method)
	{
		return $method->executeWithId($file->getID());
	}

}
