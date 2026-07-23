<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_Part extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();
		$roomId = $msg->read32();

		if (!($room = LUP_Global::getRoom($roomId)))
		{
			return $msg->rplyError('err_room');
		}

		if (!LUP_Global::isUserInRoom($user, $room))
		{
			return $msg->rplyError('err_not_in_chat');
		}

		# Announce part
		LUP_Global::part($room, $user);

		# Send sync
// 		$msg->replyBinary($msg->cmd());
	}

}

GWS_Commands::register(0x1104, new LUPWS_Part());
