<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Index;
use GDO\Core\GDT_Object;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * Table that holds room join events for a user.
 * Used in Course overview.
 *
 * @author gizmore@wechall.net
 *
 */
final class LUP_RoomVisit extends GDO
{

	###########
	### GDO ###
	###########
	public static function hasJoinedToday(LUP_Room $room, GDO_User $user)
	{
		$condition = sprintf('visit_room=%s AND visit_user=%s AND DATE(visit_at)=\'%s\'',
			$room->getID(), $user->getID(), date('Y-m-d'));
		return self::table()->countWhere($condition) > 0;
	}

	###########
	### API ###
	###########

	/**
	 * When you join a room we record room and date of visit for a user.
	 *
	 * @param LUP_Room $room
	 * @param GDO_User $user
	 */
	public static function onJoin(LUP_Room $room, GDO_User $user)
	{
		#
		# TODO: Data can be ugly when we detect every rejoin.
		# TODO: if we rejoin the same room within 10 minutes we do not add a new event?
		#
		self::blank([
			'visit_user' => $user->getID(),
			'visit_room' => $room->getID(),
		])->insert();

		self::recountTrophyJoinCounter($user);
	}

	private static function recountTrophyJoinCounter(GDO_User $user)
	{
		$trophy = LUP_Trophy::getOrCreate($user);
		$count = self::table()->select('COUNT(DISTINCT(visit_room))')->group('visit_user')->where("visit_user={$user->getID()}")->exec()->fetchVar();
		$trophy->saveVar('lt_visits', $count);
	}

	/**
	 * On a part we store the visit_left date.
	 *
	 * @param LUP_Room $room
	 * @param GDO_User $user
	 */
	public static function onPart(LUP_Room $room, GDO_User $user)
	{
		$visit = self::table()->select('*')->
		where('visit_user=' . $user->getID())->
		where('visit_room=' . $room->getID())->
		where('visit_left IS NULL')->
		order('visit_at', false)->
		first()->exec()->fetchObject();
		if ($visit)
		{
			$visit->saveVar('visit_left', Time::getDate());
		}
	}

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('visit_id'),
			GDT_User::make('visit_user'),
			GDT_Object::make('visit_room')->table(LUP_Room::table()),
			GDT_CreatedAt::make('visit_at'),
			GDT_DateTime::make('visit_left'),
			GDT_Index::make('visit_index_user')->indexColumns('visit_user'),
		];
	}

}
