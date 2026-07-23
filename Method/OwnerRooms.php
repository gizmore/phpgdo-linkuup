<?php
namespace GDO\LinkUUp\Method;

use GDO\Address\GDT_Address;
use GDO\Core\GDO;
use GDO\Core\GDT_String;
use GDO\Core\GDT_UInt;
use GDO\DB\Query;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomWorker;
use GDO\Table\MethodQueryTable;
use GDO\UI\GDT_EditButton;
use GDO\User\GDO_User;

final class OwnerRooms extends MethodQueryTable
{

	public function gdoTable(): GDO { return LUP_Room::table(); }

	public function gdoHeaders(): array
	{
		return [
			GDT_UInt::make('room_id')->label('id'),
			GDT_String::make('room_name'),
			GDT_Address::make('address'),
			GDT_EditButton::make(),
		];
	}

	public function getQuery(): Query
	{
		$user = GDO_User::current();

		if ($user->isStaff())
		{
			return LUP_Room::table()
				->select('lup_room.*, room_address_t.*, gdo_country.*')
				->joinObject('room_address', 'LEFT JOIN')
				->join('LEFT JOIN gdo_country ON gdo_country.c_iso = address_country');
		}

		$table = LUP_RoomWorker::table();
		$query = $table->select('lup_room.*, gdo_address.*')->joinObject('lrw_room')->joinObject('room_address');
		if (!$user->isStaff())
		{
			$query->where("lrw_user={$user->getID()}");
		}

		return $query;
	}

}
