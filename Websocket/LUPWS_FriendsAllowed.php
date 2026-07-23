<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Friends\Module_Friends;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Check if you may see a users friendlist.
 *
 * @author gizmore
 */
class LUPWS_FriendsAllowed extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$userid = $msg->read32u();
		$user = GDO_User::findById($userid);
		$reason = '';
		if (!Module_Friends::instance()->canViewFriends($user, $reason))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_not_allowed', [$reason]));
		}

		return $msg->replyBinary($msg->cmd());
	}

}

GWS_Commands::register(0x1135, new LUPWS_FriendsAllowed());
