<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\Table\GDT_PageMenu;
use GDO\Util\Math;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get a page of comments.
 *
 * @author gizmore
 */
class LUPWS_RoomComments extends LUPWS_RoomComment
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
		$ipp = 10;
		$numItems = $room->getCommentCount();
		$nPages = GDT_PageMenu::getPageCountS($numItems, $ipp);
		$page = Math::clampInt($msg->read16(), 1, $nPages);
		$from = GDT_PageMenu::getFromS($page, $ipp);
		$query = $room->queryComments()->limit($ipp, $from)->order('comment_created');
		$comments = $query->exec()->fetchAllObjects();

		# Pagemenu
		$response =
			GWS_Message::wr16($page) .
			GWS_Message::wr16($nPages) .
			GWS_Message::wr32($numItems) .
			GWS_Message::wr16($ipp);

		# Comment DTOs
		foreach ($comments as $comment)
		{
			$response .= $this->payloadComment($comment);
		}

		$msg->replyBinary($msg->cmd(), $response);
	}

}

GWS_Commands::register(0x1121, new LUPWS_RoomComments());
