<?php
namespace GDO\LinkUUp\Method;

use GDO\Admin\MethodAdmin;
use GDO\Core\GDO;
use GDO\LinkUUp\LUP_Room;
use GDO\QRCode\GDT_QRCode;
use GDO\Table\MethodQueryTable;
use GDO\UI\GDT_EditButton;

/**
 * Room overview for staff.
 *
 * @author gizmore
 */
final class Rooms extends MethodQueryTable
{

	use MethodAdmin;

	##################
	### QueryTable ###
	##################
	public function gdoTable(): GDO
	{
		return LUP_Room::table();
	}

	public function gdoHeaders(): array
	{
		$room = LUP_Room::table();
		return [
			GDT_EditButton::make(),
			$room->gdoColumn('room_name'),
            GDT_EditButton::make()->name('qrcode')->icon('qrcode'),
            $room->gdoColumn('room_pos'),
			$room->gdoColumn('room_color'),
			$room->gdoColumn('room_category'),
			$room->gdoColumn('room_www'),
		];
	}

}
