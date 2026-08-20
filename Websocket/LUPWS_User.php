<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDO_Module;
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

	/** Recache and send both changed profiles to the two Cuddle participants. */
	public function hookLUPUserRecache(string|int $issuerId, string|int $scannerId): void
	{
		$users = [];
		foreach (array_unique([(string)$issuerId, (string)$scannerId]) as $userId)
		{
			if ($user = GWS_Global::getOrLoadUserById($userId))
			{
				$user->tempUnset(GDO_Module::SETTINGS_KEY);
				$users[] = $user;
			}
		}
		foreach ($users as $recipient)
		{
			foreach ($users as $profile)
			{
				$payload = GWS_Message::payload(0x1106) . LUP_Global::fullUserPayload($profile);
				GWS_Global::sendBinary($recipient, $payload);
			}
		}
	}

}

GWS_Commands::register(0x1106, new LUPWS_User());
