<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_HelpRead;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Mark a help key as read.
 *
 * @author gizmore
 */
final class LUPWS_HelpRead extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		LUP_HelpRead::blank([
			'lhr_user' => $msg->user()->getID(),
			'lhr_key' => $msg->readString(),
		])->insert();
		$msg->replyBinary($msg->cmd());
	}

}

GWS_Commands::register(0x1191, new LUPWS_HelpRead());
