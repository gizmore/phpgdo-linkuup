<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Date\Time;
use GDO\LinkUUp\LUP_QueryMessage;
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
		$oid = $msg->read32u(); # Other
		$time = $msg->read32u(); # Timecut

		# Build query
		$table = LUP_QueryMessage::table();
		$ifquery2 = "IF(lupqm_from=$uid, lupqm_from_deleted, lupqm_to_deleted) me_deleted";
		$query = $table->select("*, $ifquery2");
		$query->where("(lupqm_from=$uid AND lupqm_to=$oid) OR (lupqm_to=$uid AND lupqm_from=$oid)");
		$query->having('me_deleted=0');
		if ($time)
		{
			$timecut = Time::getDate($time);
			$query->where("lupqm_created<='$timecut'");
		}
		$query->order('lupqm_created DESC');
		$query->limit(11);

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
