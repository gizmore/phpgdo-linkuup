<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_Object;
use GDO\DB\Result;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * This relation holds the coworker<=>room relationship.
 *
 * @version 7.0.1
 * @author gizmore
 */
class LUP_RoomWorker extends GDO
{

	public static function addWorker(LUP_Room $room, GDO_User $owner)
	{
		return self::table()->blank([
			'lrw_room' => $room->getID(),
			'lrw_user' => $owner->getID(),
		])->replace();
	}

    public static function isWorker(LUP_Room $room, GDO_User $user): bool
    {
        return self::table()->getById($room->getID(), $user->getId());
    }

	##############
	### Static ###
	##############

	public static function removeWorker(LUP_Room $room, GDO_User $owner)
	{
		return self::table()->deleteWhere("lrw_room={$room->getID()} AND lrw_user={$owner->getID()}")->exec();
	}

	public function gdoColumns(): array
	{
		return [
			GDT_Object::make('lrw_room')->table(LUP_Room::table())->primary()->notNull()->cascade(),
			GDT_User::make('lrw_user')->primary()->notNull()->cascade(),
			GDT_Checkbox::make('lrw_owner')->initial('0')->notNull(),
			GDT_CreatedAt::make('lrw_created'),
			GDT_CreatedBy::make('lrw_creator'),
		];
	}

	#################
	### Coworkers ###
	#################

	/**
	 * Get Coworkers for a room.
	 */
	public function getCoworkers(LUP_Room $room): array
	{
		return $this->getCoworkersResult($room)->fetchAllObjects();
	}

	/**
	 * Get Coworkers Query Result for a room.
	 */
	public function getCoworkersResult(LUP_Room $room): Result
	{
		return $this->select('lrw_user_t.*')
			->where("lrw_room={$room->getID()}")
			->joinObject('lrw_user')
			->fetchTable(GDO_User::table())
			->exec();
	}

}
