<?php
namespace GDO\LinkUUp\Websocket;

use GDO\DB\Database;
use GDO\LinkUUp\LUP_QueryMessage;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Delete all conversation with a user / whole query
 *
 * @author gizmore
 */
class LUPWS_QueryDelete extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		# Params
		$uid = $msg->user()->getID();
		$oid = $msg->read32u();

		$db = Database::instance();
		$table = LUP_QueryMessage::table();
		$numDeleted = 0;

		# Delete sent to oid
		$query = $table->update()->set('lupqm_from_deleted=1');
		$query->where("lupqm_from=$uid AND lupqm_to=$oid");
		$query->exec();
		$numDeleted += $db->affectedRows();

		# Delete received from oid
		$query = $table->update()->set('lupqm_to_deleted=1');
		$query->where("lupqm_from=$oid AND lupqm_to=$uid");
		$query->exec();
		$numDeleted += $db->affectedRows();

        # TODO: notify both of deletion.

		# Reply how much went deleted
		$payload = GWS_Message::wr32($numDeleted);
		$msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x110C, new LUPWS_QueryDelete());
