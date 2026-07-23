<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_MessageSent;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_Message extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();
		if (!($room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->rplyError('err_room');
		}

		if ($room->isDisabled())
		{
			return $msg->rplyError('err_room_disabled');
		}

		if (!isset(LUP_Global::$ROOM_USERS[$room->getID()][$user->getID()]))
		{
			return $msg->rplyError('err_not_in_room');
		}

		if ($this->cfgTicketEngine())
		{
			if (!$room->getCurrentTicket())
			{
				return $msg->rplyError('err_ticket');
			}
		}

		# Trophy statistics
		if ($trophy = LUP_Global::trophyForUser($user))
		{
			$trophy->countUp('lt_chat_sent');
		}

		# Owner statistics
		LUP_MessageSent::messageSent($room);

		# Send chat messages
		LUP_Global::chat($room, $user, $msg);
	}

}

GWS_Commands::register(0x1107, new LUPWS_Message());
