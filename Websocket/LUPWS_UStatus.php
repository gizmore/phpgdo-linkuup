<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_UStatus extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();
		$status = $msg->readString();

		$trophy = LUP_Global::trophyForUser($user);
		$trophy->saveVars([
			'lt_status' => $status,
		]);

		$payload2 =
			GWS_Message::wr32($user->getID()) .
			GWS_Message::wrS($status);
		$payload = GWS_Message::payload($msg->cmd()) . $payload2;

		GWS_Global::broadcastBinary($payload);

		$msg->replyBinary($msg->cmd(), $payload2);
	}

}

GWS_Commands::register(0x1110, new LUPWS_UStatus());
