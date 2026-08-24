<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_QueryThread;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get all conversation metadata (Queries) via the last message for a chat partner / chat.
 *
 * @author gizmore
 */
class LUPWS_Queries extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$uid = $msg->user()->getID();

		$table = LUP_QueryThread::table();
		$ifquery = "IF(lupqt_user_a=$uid, lupqt_a_deleted, lupqt_b_deleted) me_deleted";
		$query = $table->select("*, $ifquery");
		$query->where("lupqt_user_a=$uid OR lupqt_user_b=$uid");
		$query->having('me_deleted=0');
		$query->order('lupqt_updated DESC');

		$result = $query->exec();

		$payload = '';
		while ($thread = $result->fetchObject())
		{
			$payload .= $this->gdoToBinary($thread);
		}

		return $msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x110A, new LUPWS_Queries());
