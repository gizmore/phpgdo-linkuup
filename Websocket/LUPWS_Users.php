<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get multiple user info in one call.
 *
 * @author gizmore
 */
class LUPWS_Users extends LUPWS_User
{

	public function execute(GWS_Message $msg)
	{
		$response = '';
		while ($msg->hasMore())
		{
			$response .= $this->getUserResponse($msg->read32());
		}
		$msg->replyBinary($msg->cmd(), $response);
	}

}

GWS_Commands::register(0x1105, new LUPWS_Users());
