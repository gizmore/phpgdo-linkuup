<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDT_Response;
use GDO\Core\Method;
use GDO\Form\GDT_Form;
use GDO\Friends\GDO_Friendship;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Notification;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomVote;
use GDO\LinkUUp\Method\WriteRoomComment;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_CommandForm;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get own comment and rating for a room and user.
 *
 * @author gizmore
 */
class LUPWS_SaveUserComment extends GWS_CommandForm
{

	private LUP_Room $room;

	public function getMethod() { return WriteRoomComment::make(); }

	public function fillRequestVars(GWS_Message $msg, Method $method)
	{
		$id = $this->room->getID();
		$method->gdoParameter('id', false)->var($id);
	}

	public function execute(GWS_Message $msg)
	{
		if (!($this->room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->rplyError('err_room');
		}
        $msg->move(-4);
		if ($this->room->isDisabled())
		{
			return $msg->rplyError('err_room_disabled');
		}
		return parent::execute($msg);
	}

	public function replySuccess(GWS_Message $msg, GDT_Form $form, GDT_Response $response)
	{
		$this->sendNotifications($msg, $form);
		return parent::replySuccess($msg, $form, $response);
	}

	/**
	 * Send notifications to friends after user has commented on a room.
	 *
	 * @param GWS_Message $msg
	 * @param GDT_Form $form
	 */
	private function sendNotifications(GWS_Message $msg, GDT_Form $form)
	{
		$result = GDO_Friendship::getFriendsQuery($msg->user())->exec();
		$table = GDO_User::table();
		$userid = (int)$msg->user()->getID();
		$roomid = (int)$this->room->getID();
		$comment = $form->getFormVar('comment_message');
		$vote = LUP_RoomVote::table()->getVote($msg->user(), $this->room);
		$score = $vote ? $vote->getScore() : 0;
		while ($user = $table->fetch($result))
		{
			# Insert into notification table
			$notification = LUP_Notification::blank([
				'note_user' => $user->getID(),
				'note_data' => json_encode([
					'type' => 'commented',
					'user' => $userid,
					'room' => $roomid,
					'comment' => $comment,
					'rating' => (int)$score,
				]),
			])->insert();
			# Send to friends
			$payload = $msg->wrCmd(0x1141) . $this->gdoToBinary($notification);
			GWS_Global::sendBinary($user, $payload);
		}
	}

}

GWS_Commands::register(0x1124, new LUPWS_SaveUserComment());
