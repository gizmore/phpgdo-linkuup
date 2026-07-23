<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Friends\GDO_Friendship;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Notification;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomVisit;
use GDO\LinkUUp\LUPWS_Command;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_Join extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();
		$roomId = $msg->read32();
		$lat = $msg->readFloat();
		$lng = $msg->readFloat();
		$secret = $msg->readString();

		if ($user->isGhost())
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_please_auth'));
		}

		if (!($room = LUP_Global::getRoom($roomId)))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_room'));
		}

		if ($room->isDisabled())
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_room_disabled'));
		}

		if (LUP_Global::isUserInRoom($user, $room))
		{
			# Send GDO_User list on join
			$payload = LUP_Global::userListPayload($room);
			GWS_Global::sendBinary($user, $payload);
			return; # $msg->rplyError('err_join_twice');
		}

		if (!LUP_Global::isVIP($user))
		{
			if (!$room->isInChatRange($lat, $lng))
			{
				return $msg->rplyError('err_room_not_near');
			}
		}

		if ($this->cfgTicketEngine())
		{
			if (!$ticket = $room->getCurrentTicket())
			{
				return $msg->rplyError('err_ticket');
			}
			if (!$ticket->checkSecret($secret))
			{
				return $msg->rplyError('err_ticket_secret');
			}
		}

		# Part other chats before join?
		if ($this->cfgOnlyOneChat())
		{
			$rooms = LUP_Global::getRoomsForUser($user);
			foreach ($rooms as $_room)
			{
				LUP_Global::part($_room, $user);
			}
		}

		$hasJoinedToday = LUP_RoomVisit::hasJoinedToday($room, $user);

		if (!$hasJoinedToday)
		{
			# Store join
			LUP_RoomVisit::onJoin($room, $user);
			$this->sendUserUpdate($user);
		}

		# Announce join
		LUP_Global::join($room, $user);

		if (!$hasJoinedToday)
		{
			# Create and send notifications
			$this->sendNotifiactions($msg, $room);
		}


		# Send GDO_User list on join
		$payload = LUP_Global::userListPayload($room);
		GWS_Global::sendBinary($user, $payload);

		# Send Sync JOINED Response room name.
// 		$payload = $room->getName();
// 		GWS_Global::sendCommand($user, 'JOINED', GWS_Commands::payload($payload, $mid));
	}

	private function sendUserUpdate(GDO_User $user)
	{
		$payload = GWS_Message::wrCmd(0x1106);
		$payload .= LUP_Global::fullUserPayload($user);
		GWS_Global::broadcastBinary($payload);
	}

	/**
	 * Send room join notification to friends.
	 *
	 * @param GWS_Message $msg
	 * @param LUP_Room $room
	 */
	private function sendNotifiactions(GWS_Message $msg, LUP_Room $room)
	{
		$result = GDO_Friendship::getFriendsQuery($msg->user())->exec();
		$table = GDO_User::table();
		$roomid = (int)$room->getID();
		$userid = $msg->user()->getID();
		while ($user = $table->fetch($result))
		{
			# Insert into notification table
			$notification = LUP_Notification::blank([
				'note_user' => $user->getID(),
				'note_data' => json_encode(['type' => 'join', 'user' => $userid, 'room' => $roomid]),
			])->insert();
			# Send to friends
			$payload = $msg->wrCmd(0x1141) . $this->gdoToBinary($notification);
			GWS_Global::sendBinary($user, $payload);
		}
	}

}

GWS_Commands::register(0x1103, new LUPWS_Join());
