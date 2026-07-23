<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Comments\GDO_Comment;
use GDO\Date\Time;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get the newest comment for a room.
 *
 * @author gizmore
 */
class LUPWS_RoomComment extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		if (!($room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->rplyError('err_room');
		}
		if ($room->isDisabled())
		{
			return $msg->rplyError('err_room_disabled');
		}

		# Only the newest comment.
		$comments = [];
		if ($comment = $room->queryComments()->order('comment_created DESC')->first()->exec()->fetchObject())
		{
			$comments[] = $comment;
		}

		$this->executeWithComments($msg, $room, $comments);
	}

	/**
	 *
	 * @param GWS_Message $msg
	 * @param LUP_Room $room
	 * @param Comment[]
	 */
	public function executeWithComments(GWS_Message $msg, LUP_Room $room, array $comments)
	{
		$response = GWS_Message::wr32($room->getCommentCount());
		if (count($comments))
		{
			$response .= $this->payloadComment($comments[0]);
		}
		$msg->replyBinary($msg->cmd(), $response);
	}

	public function payloadComment(GDO_Comment $comment)
	{
		return
			GWS_Message::wr32($comment->getID()) .
			GWS_Message::wr32($comment->getCreatorID()) .
			// 		GWS_Message::wrS($comment->displayInput()).
			GWS_Message::wrS($comment->displayMessage()) .
			GWS_Message::wr32(Time::getTimestamp($comment->getCreateDate()));
	}

}

GWS_Commands::register(0x1122, new LUPWS_RoomComment());
