<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Notification;
use GDO\LinkUUp\LUP_QueryMessage;
use GDO\LinkUUp\LUP_QueryThread;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get unread notification count for a user.
 * Also add unread PM/Query.
 *
 * @author gizmore
 */
class LUPWS_NotificationsCount extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$userId = $msg->user()->getID();

		$countNotes = LUP_Notification::table()->countWhere("note_user=$userId AND note_read IS NULL");

		$query = LUP_QueryMessage::table()->select('COUNT(*)');
		$query->join('JOIN lup_querythread ON lupqm_thread=lupqt_id');
		$query->where("lupqm_to=$userId AND lupqm_read IS NULL");
		$query->where("IF(lupqt_user_a=$userId, lupqt_a_deleted, lupqt_b_deleted) = 0");
		$countQueries = (int)$query->exec()->fetchVar();

		$payload = GWS_Message::wr32($countNotes);
		$payload .= GWS_Message::wr32($countQueries);
		$msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x1143, new LUPWS_NotificationsCount());
