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
final class LUPWS_HelpReset extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$userid = $msg->user()->getID();
		LUP_HelpRead::table()->deleteWhere("lhr_user=$userid");
		$msg->replyBinary($msg->cmd());
	}

}

GWS_Commands::register(0x1192, new LUPWS_HelpReset());
