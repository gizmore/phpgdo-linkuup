<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_QueryMessage;
use GDO\LinkUUp\LUP_QueryThread;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get messages for a private conversation.
 *
 * @author gizmore@wechall.net
 */
class LUPWS_QueryMessages extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		# Parameters
		$uid = $msg->user()->getID(); # Me
		$threadId = $msg->read32u();

		# Build query
		$table = LUP_QueryMessage::table();
		$ifquery = "IF(lupqt_user_a=$uid, lupqt_a_deleted, lupqt_b_deleted) me_deleted";
		$query = $table->select("lup_querymessage.*, $ifquery");
		$query->join('JOIN lup_querythread ON lupqm_thread=lupqt_id');
		$query->where("lupqm_thread=$threadId");
		$query->where("lupqt_user_a=$uid OR lupqt_user_b=$uid");
		$query->having('me_deleted=0');
		$query->order('lupqm_created ASC');

		# Run query
		$result = $query->exec();

		# Fetch and build payload
		$payload = '';
		while ($message = $result->fetchObject())
		{
			$payload .= $this->gdoToBinary($message);
		}

		# Reply
		return $msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x110B, new LUPWS_QueryMessages());
