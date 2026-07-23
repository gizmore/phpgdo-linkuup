<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get the top comments for a room.
 *
 * @author gizmore
 */
class LUPWS_RoomTopComments extends LUPWS_RoomComment
{

	public function execute(GWS_Message $msg)
	{
		# Sanity
		if (!($room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->rplyError('err_room');
		}
		if ($room->isDisabled())
		{
			return $msg->rplyError('err_room_disabled');
		}

		# Query
		$limit = Module_LinkUUp::instance()->cfgNumTopComments();
		$query = $room->queryComments()->limit($limit)->
		order('comment_created', false)->
		order('comment_likes', false)->
		order('comment_top');
		$comments = $query->exec()->fetchAllObjects();

		# Comment DTOs
		$response = '';
		foreach ($comments as $comment)
		{
			$response .= $this->payloadComment($comment);
		}

		$msg->replyBinary($msg->cmd(), $response);
	}

}

GWS_Commands::register(0x1126, new LUPWS_RoomTopComments());
