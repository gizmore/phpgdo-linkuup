<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDT_Response;
use GDO\Form\GDT_Form;
use GDO\Friends\GDO_FriendRequest;
use GDO\Friends\Method\Request;
use GDO\LinkUUp\LUP_Notification;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_CommandForm;
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
class LUPWS_FriendsRequest extends GWS_CommandForm
{

	# Map to form
	public function getMethod() { return Request::make(); }

	/**
	 * via ws
	 * {@inheritDoc}
	 *
	 * @see GWS_CommandForm::postExecute
	 */
	public function postExecute(GWS_Message $msg, GDT_Form $form, GDT_Response $response)
	{
		if (!$response->isError())
		{
			$this->sendNotifications($msg->user()->getID(), $form->getFormVar('frq_friend'));
		}
		parent::postExecute($msg, $form, $response);
	}

	private function sendNotifications($userid, $friendid)
	{
		# Send to receiver
		$user = GDO_User::getById($friendid);
		$notification = LUP_Notification::blank([
			'note_user' => $user->getID(),
			'note_data' => json_encode(['type' => 'friendrequest', 'user' => $userid, 'friend' => $friendid]),
		])->insert();
		$payload = GWS_Message::wrCmd(0x1141) . $this->gdoToBinary($notification);
		GWS_Global::sendBinary($user, $payload);

		# Send to initiator
		$user = GDO_User::getById($userid);
		$notification = LUP_Notification::blank([
			'note_user' => $user->getID(),
			'note_data' => json_encode(['type' => 'friendrequested', 'user' => $userid, 'friend' => $friendid]),
		])->insert();
		$payload = GWS_Message::wrCmd(0x1141) . $this->gdoToBinary($notification);
		GWS_Global::sendBinary($user, $payload);
	}

	/**
	 * Via web hook
	 *
	 * @param int $requestId
	 */
	public function hookFriendsRequest($requestId)
	{
		$request = GDO_FriendRequest::findByGID($requestId);
		$uid = (int)$request->getUserID();
		$fid = (int)$request->getFriendID();
		$this->sendNotifications($uid, $fid);
	}

}

GWS_Commands::register(0x1131, new LUPWS_FriendsRequest());
