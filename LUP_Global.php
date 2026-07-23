<?php
namespace GDO\LinkUUp;

use GDO\Avatar\GDO_Avatar;
use GDO\Friends\GDO_FriendRequest;
use GDO\Friends\GDO_Friendship;
use GDO\Friends\GDT_FriendRelation;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

final class LUP_Global
{

	public static $ROOMS = [];
	/**
	 * @var GDO_User[]
	 */
	public static $ROOM_USERS = [];
	public static $USER_AVATARS = [];
	public static $USER_STATUS = [];
	public static $USER_TROPHY = [];
	public static $USER_LIKES = [];

	##################
	### Visibility ###
	##################
	/**
	 * Current user positions.
	 *
	 * @var array
	 */
	public static $POSITIONS = [];

	public static function userSeesUser(GDO_User $a, GDO_User $b)
	{
		$namesSeeingA = array_keys(self::usersSeeing($a));
		return in_array($b->getID(), $namesSeeingA);
	}

	##############
	### Helper ###
	##############

	public static function usersSeeing(GDO_User $user)
	{
		$back = [];
		foreach (self::$ROOM_USERS as $users)
		{
			if (isset($users[$user->getID()]))
			{
				foreach ($users as $user_id => $_user)
				{
					if (!isset($back[$user_id]))
					{
						$back[$user_id] = $_user;
					}
				}
			}
		}
		return $back;
	}

	public static function userListPayload(LUP_Room $room)
	{
		return
			GWS_Message::payload(0x1105) .
			self::userListPayloadData($room);
	}

	public static function userListPayloadData(LUP_Room $room, $withRoomId = true)
	{
		$payload = '';
		if ($withRoomId)
		{
			$payload = GWS_Message::wr32($room->getID());
		}
		if (isset(self::$ROOM_USERS[$room->getID()]))
		{
			foreach (self::$ROOM_USERS[$room->getID()] as $user)
			{
				$payload .= GWS_Message::wr32($user->getID());
			}
		}
		return $payload;
	}

	public static function fullUserPayload(GDO_User $user)
	{
		return
			GWS_Message::wr32($user->getID()) .
			GWS_Message::wr16($user->gdoColumn('user_type')->value($user->getType())->enumIndex()) .
			GWS_Message::wr32($user->getLevel()) .
			GWS_Message::wrS($user->getName()) .
			GWS_Message::wrS($user->getGuestName()) .
//			GWS_Message::wr16('') . # real name
			GWS_Message::wr32(self::avatarFileForUser($user)) .
//			GWS_Message::wr16(self::avatarVersionForUser($user)) .
			GWS_Message::wr16(self::genderPayload($user)) .
			GWS_Message::wr16(self::sexualOrientationPayload($user)) .
			GWS_Message::wr16(self::sexualInterestPayload($user)) .
			GWS_Message::wr16(self::friendshipStatusPayload($user)) .
			GWS_Message::wr8(self::friendshipPendingPayload($user)) .
			GWS_Message::wrS(self::countryPayload($user)) .
			self::trophyDataForUser($user);
	}

	public static function avatarFileForUser(GDO_User $user)
	{
		return self::avatarForUser($user)->getFileID();
	}

// 	private static function birthdayPayload(GDO_User $user)
// 	{
// 	    $bday = Module_Birthday::instance()->userSettingValue($user, 'birthday');
// 	    return $bday;
// 	}

	/**
	 *
	 * @param GDO_User $user
	 *
	 * @return GDO_Avatar
	 */
	public static function avatarForUser(GDO_User $user)
	{
		return GDO_Avatar::forUser($user);
	}

	private static function genderPayload(GDO_User $user)
	{
		return $user->setting('User', 'gender')->enumIndex();
	}

	private static function sexualOrientationPayload(GDO_User $user)
	{
		if (!$user->isPersisted())
		{
			return 0;
		}
		return self::sexualOrientationFor($user)->enumIndex();
	}

	/**
	 * @param GDO_User $user
	 *
	 * @return GDT_SexualOrientation
	 */
	private static function sexualOrientationFor(GDO_User $user)
	{
		return Module_LinkUUp::instance()->userSetting($user, 'lup_sexo');
	}

	private static function sexualInterestPayload(GDO_User $user)
	{
		if (!$user->isPersisted())
		{
			return 0;
		}
		return self::sexualInterestFor($user)->enumIndex();
	}

	/**
	 * @param GDO_User $user
	 *
	 * @return GDT_RelationInterest
	 */
	private static function sexualInterestFor(GDO_User $user)
	{
		return Module_LinkUUp::instance()->userSetting($user, 'lup_interest');
	}

	####################
	### Trophy Cache ###
	####################

	private static function friendshipStatusPayload(GDO_User $user)
	{
		$enumValue = GDO_Friendship::getRelationBetween(GDO_User::current(), $user);
		return GDT_FriendRelation::make()->enumIndexFor($enumValue);
	}

	private static function friendshipPendingPayload(GDO_User $user)
	{
		$pending = GDO_FriendRequest::table()->getPendingFor(GDO_User::current(), $user);
		return $pending ? 1 : 0;
	}

	private static function countryPayload(GDO_User $user)
	{
		return $user->settingVar('Country', 'country_of_origin');
	}


	####################
	### Avatar Cache ###
	####################

	public static function trophyDataForUser(GDO_User $user)
	{
		$trophy = self::trophyForUser($user);
		return
			GWS_Message::wr32(GDO_Friendship::count($user)) .
			GWS_Message::wrS($trophy->getStatus()) .
			GWS_Message::wr16($trophy->isVIP() ? 1 : 0) .
			GWS_Message::wr32($trophy->getLikeCount()) .
			GWS_Message::wr32($trophy->getChatSent()) .
			GWS_Message::wr32($trophy->getQuerySent()) .
			GWS_Message::wr32($trophy->getQueryRecieved()) .
            GWS_Message::wr32($trophy->getVisits()).
            GWS_Message::wr32(Module_LinkUUp::instance()->cfgCuddles($user));
	}

	/**
	 * @param GDO_User $user
	 *
	 * @return LUP_Trophy
	 */
	public static function trophyForUser(GDO_User $user)
	{
		if (!$user->isPersisted())
		{
			return LUP_Trophy::blank(['lt_uid' => $user->getID()]);
		}
		return LUP_Trophy::getOrCreate($user);
	}

	public static function isVIP(GDO_User $user)
	{
		return self::trophyForUser($user)->isVIP();
	}

	#############
	### Rooms ###
	#############

	public static function processAvatarChange(GDO_User $user, array $row)
	{
		self::$USER_AVATARS[$user->getID()] = [
			'avatar_mode' => $row['avatar_mode'],
			'avatar_file' => $row['avatar_file'],
			'avatar_version' => $row['avatar_version'],
		];
	}

	/**
	 * @param int $id
	 *
	 * @return LUP_Room
	 */
	public static function getRoom($id)
	{
		if (!isset(self::$ROOMS[$id]))
		{
			if (!$room = LUP_Room::getById($id))
			{
				return false;
			}
			self::$ROOMS[$id] = $room;
			self::$ROOM_USERS[$id] = [];
		}
		return self::$ROOMS[$id];
	}

	public static function isUserInRoom(GDO_User $user, LUP_Room $room)
	{
		return isset(self::$ROOM_USERS[$room->getID()][$user->getID()]);
	}

	public static function userQuit(GDO_User $user)
	{
		foreach (self::getRoomsForUser($user) as $room)
		{
			self::part($room, $user);
		}
	}

	/**
	 * @param GDO_User $user
	 *
	 * @return LUP_Room[]
	 */
	public static function getRoomsForUser(GDO_User $user)
	{
		$userid = $user->getID();
		$rooms = [];
		foreach (self::$ROOM_USERS as $roomId => $users)
		{
			if (isset($users[$userid]))
			{
				$rooms[] = self::$ROOMS[$roomId];
			}
		}
		return $rooms;
	}

	public static function part(LUP_Room $room, GDO_User $user)
	{
		LUP_RoomVisit::onPart($room, $user);

		$payload = GWS_Message::payload(0x1104);
		$payload .= GWS_Message::wrTS();
		$payload .= GWS_Message::wr32($room->getID());
		$payload .= GWS_Message::wr32($user->getID());
		GWS_Global::broadcastBinary($payload);
		unset(self::$ROOM_USERS[$room->getID()][$user->getID()]);
	}

	public static function join(LUP_Room $room, GDO_User $user)
	{
		$id = $room->getID();
		if (!isset(self::$ROOM_USERS[$id][$user->getID()]))
		{
			self::$ROOM_USERS[$id][$user->getID()] = $user;

			$payload = GWS_Message::payload(0x1103);
			$payload .= GWS_Message::wrTS();
			$payload .= GWS_Message::wr32($room->getID());
			$payload .= GWS_Message::wr32($user->getID());
			# Announce to all
			GWS_Global::broadcastBinary($payload);
		}
	}

	###########
	### GPS ###
	###########

	public static function chat(LUP_Room $room, GDO_User $user, GWS_Message $message)
	{
		# User, room, msg
		$payload = GWS_Message::wr32(time());
		$payload .= GWS_Message::wr32($user->getID());
		$payload .= GWS_Message::wr32($room->getID());
		$payload .= GWS_Message::wrS($message->readString());

		# Payload2 goes async to all users
		$payload2 = GWS_Message::payload(0x1107);
		$payload2 .= $payload;
		foreach (self::$ROOM_USERS[$room->getID()] as $_user)
		{
			if ($user !== $_user)
			{
				GWS_Global::sendBinary($_user, $payload2);
			}
		}

		# Payload1 goes sync back
		$message->replyBinary($message->cmd(), $payload);
	}

	public static function updateGPS(GDO_User $user, $lat, $lng)
	{
		self::$POSITIONS[$user->getID()] = [$lat, $lng];
	}

}
