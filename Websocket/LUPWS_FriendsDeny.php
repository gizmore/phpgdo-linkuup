<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Friends\GDO_FriendRequest;
use GDO\Friends\Method\Deny;
use GDO\LinkUUp\LUP_Global;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/** Deny an incoming friend request from a profile action menu. */
final class LUPWS_FriendsDeny extends GWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$senderId = $msg->read32u();
		$request = GDO_FriendRequest::findById($senderId, $msg->user()->getID());
		Deny::make()->executeWithRequest($request);
		$sender = GDO_User::getById($senderId);
		return $msg->replyBinary($msg->cmd(), LUP_Global::fullUserPayload($sender));
	}
}

GWS_Commands::register(0x1137, new LUPWS_FriendsDeny());
