<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Friends\GDO_FriendRequest;
use GDO\Friends\GDO_Friendship;
use GDO\Friends\Method\Accept;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Notification;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

/**
 * WebSocket command for friend requests.
 *
 * 1. Hook friend requests and send websocket packet to notify friend target.
 *
 * @author gizmore
 */
class LUPWS_FriendsAccept extends GWS_Command
{

	public function hookFriendsAccept($userid, $friendid)
	{
		$this->sendNotifications($userid, $friendid);
	}

	private function sendNotifications($userid, $friendid)
	{
		$sent = [];
		$result = GDO_Friendship::getFriendsQuery(GDO_User::getById($userid))->exec();
		$table = GDO_User::table();
		while ($user = $table->fetch($result))
		{
			# Insert into notification table
			$notification = LUP_Notification::blank([
				'note_user' => $user->getID(),
				'note_data' => json_encode(['type' => 'friends', 'user' => $userid, 'friend' => $friendid]),
			])->insert();
			# Send to friends
			$payload = GWS_Message::wrCmd(0x1141) . $this->gdoToBinary($notification);
			GWS_Global::sendBinary($user, $payload);
			$sent[$user->getID()] = true;
		}

		$result = GDO_Friendship::getFriendsQuery(GDO_User::getById($friendid))->exec();
		$table = GDO_User::table();
		while ($user = $table->fetch($result))
		{
			if (isset($sent[$user->getID()]))
			{
				continue; # skip already sent
			}
			# Insert into notification table
			$notification = LUP_Notification::blank([
				'note_user' => $user->getID(),
				'note_data' => json_encode(['type' => 'friends', 'user' => $userid, 'friend' => $friendid]),
			])->insert();
			# Send to friends
			$payload = GWS_Message::wrCmd(0x1141) . $this->gdoToBinary($notification);
			GWS_Global::sendBinary($user, $payload);
		}
	}

	public function execute(GWS_Message $msg)
	{
		# Get request
		$userid = $msg->read32u();
		$friendid = $msg->read32u();
		$request = GDO_FriendRequest::findById($userid, $friendid);

		# Exec http method
		Accept::make()->executeWithRequest($request);

		# Send notifications
		$this->sendNotifications($userid, $friendid);

		# Reply user payload
		$friend = GWS_Global::getOrLoadUserById($userid);
		return $msg->replyBinary($msg->cmd(), LUP_Global::fullUserPayload($friend));
	}

}

GWS_Commands::register(0x1132, new LUPWS_FriendsAccept());
