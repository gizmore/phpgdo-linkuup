<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Comments\GDO_Comment;
use GDO\Comments\Method\Delete;
use GDO\Date\Time;
use GDO\LinkUUp\LUP_RoomComments;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Delete a comment
 *
 * @author gizmore
 */
class LUPWS_RoomCommentDelete extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$id = $msg->read32u();
		$comment = GDO_Comment::findById($id);
		if ($comment->isDeleted())
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_comment_already_deleted'));
		}

		if (!LUP_RoomComments::table()->canDeleteComment($comment, $msg->user()))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_permission_delete'));
		}

		$comment->saveVars([
			'comment_deleted' => Time::getDate(),
			'comment_deletor' => GDO_User::current()->getID(),
		]);

		Delete::make()->sendEmail($comment);

		$msg->replyBinary($msg->cmd());
	}

}

GWS_Commands::register(0x1127, new LUPWS_RoomCommentDelete());
