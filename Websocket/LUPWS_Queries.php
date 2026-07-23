<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_QueryMessage;
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

		$table = LUP_QueryMessage::table();

		$ifquery = "IF(lupqm_from=$uid, lupqm_to, lupqm_from) other_user";
		$ifquery2 = "IF(lupqm_from=$uid, lupqm_from_deleted, lupqm_to_deleted) me_deleted";
		$query = $table->select("*, $ifquery, $ifquery2");
		$query->where("lupqm_from=$uid OR lupqm_to=$uid");
		$query->having('me_deleted=0');
		$query->order('lupqm_created DESC');

		$result = $query->exec();

		$chats = [];
		$payload = '';
		while ($message = $result->fetchAssoc())
		{
			$other = $message['other_user'];
			if (!isset($chats[$other]))
			{
				$message = LUP_QueryMessage::blank($message);
				$chats[$other] = $message;
				$payload .= $this->gdoToBinary($message);
			}
		}

		return $msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x110A, new LUPWS_Queries());
