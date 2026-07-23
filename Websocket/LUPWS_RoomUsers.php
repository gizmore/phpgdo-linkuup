<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get user list for a room.
 *
 * @author gizmore
 */
class LUPWS_RoomUsers extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		if (!($room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->rplyError('err_room');
		}
		if ($room->isDisabled())
		{
			return $msg->rplyError('err_room_disabled');
		}

		$msg->replyBinary($msg->cmd(), LUP_Global::userListPayloadData($room));
	}

}

GWS_Commands::register(0x1125, new LUPWS_RoomUsers());
