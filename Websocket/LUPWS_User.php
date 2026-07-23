<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get user info.
 *
 * @author gizmore
 */
class LUPWS_User extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$response = $this->getUserResponse($msg->read32());
		$msg->replyBinary($msg->cmd(), $response);
	}

	public function getUserResponse($userId)
	{
		if (!($user = GWS_Global::getOrLoadUserById($userId)))
		{
			$user = GDO_User::ghost()->setVar('user_id', $userId);
		}
		return LUP_Global::fullUserPayload($user);
	}

}

GWS_Commands::register(0x1106, new LUPWS_User());
