<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_UInt;
use GDO\Date\GDT_DateTime;

/**
 * Statistics message counter per room and day.
 *
 * @version 7.0.1
 * @author gizmore
 */
final class LUP_MessageSent extends GDO
{

	public static function messageSent(LUP_Room $room)
	{
		$row = self::getOrBlank($room);
		$row->setVar('lms_count', $row->gdoVar('lms_count') + 1);
		return $row->replace();
	}

	private static function getOrBlank(LUP_Room $room)
	{
		$date = self::currentDate();
		if (!($row = self::table()->getById($date, $room->getID())))
		{
			$row = self::blank([
				'lms_date' => $date,
				'lms_room' => $room->getID(),
				'lms_count' => '0',
			]);
		}
		return $row;
	}

	private static function currentDate()
	{
		return date('Y-m-d H:00:00');
	}

	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_DateTime::make('lms_date')->primary(),
			GDT_Object::make('lms_room')->table(LUP_Room::table())->primary()->cascade(),
			GDT_UInt::make('lms_count')->notNull(),
		];
	}


}
