<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUPWS_Command;
use GDO\User\Method\Profile;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_ProfileView extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		if (!($profileUser = GWS_Global::getOrLoadUserById($msg->read32())))
		{
			return $msg->rplyError('err_user');
		}

		# Increase views
		Profile::make()->onProfileView($profileUser);

		# Response
		$payload = GWS_Message::wr32($profileUser->getID());
		$payload .= GWS_Message::wr32($profileUser->settingVar('User', 'profile_views'));
		return $msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x1111, new LUPWS_ProfileView());
