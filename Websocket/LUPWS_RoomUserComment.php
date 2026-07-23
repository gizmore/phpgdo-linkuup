<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Room;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get own comment and rating for a room and user.
 *
 * @author gizmore
 */
class LUPWS_RoomUserComment extends LUPWS_RoomComment
{

	public function executeWithComments(GWS_Message $msg, LUP_Room $room, array $comments)
	{
		$user = $msg->user();

		$vote = $room->getVote($user);
		$score = $vote ? $vote->getScore() : 0;

		$comment = $room->getUserComment($user);
		$text = $comment ? $comment->displayMessage() : '';
		$input = $comment ? $comment->displayInput() : '';
		$likes = $comment ? $comment->getLikeCount() : 0;

		$response =
			GWS_Message::wr32($room->getID()) .
			GWS_Message::wr32($user->getID()) .
			GWS_Message::wr8($score) .
			GWS_Message::wrS($text) .
			GWS_Message::wrS($input) .
			GWS_Message::wr32($likes);

		$msg->replyBinary($msg->cmd(), $response);
	}

}

GWS_Commands::register(0x1123, new LUPWS_RoomUserComment());
