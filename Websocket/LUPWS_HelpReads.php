<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_HelpRead;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get a list of read help keys.
 *
 * @author gizmore
 */
final class LUPWS_HelpReads extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$userid = $msg->user()->getID();
		$read = LUP_HelpRead::table()->select('lhr_key')->where("lhr_user=$userid")->exec()->fetchAllVars();
		$msg->replyText($msg->cmd(), json_encode($read));
	}

}

GWS_Commands::register(0x1190, new LUPWS_HelpReads());
