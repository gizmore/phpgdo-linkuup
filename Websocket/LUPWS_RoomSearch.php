<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDO;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/** Lightweight catalogue search; it never sends the complete room list. */
final class LUPWS_RoomSearch extends LUPWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$query = trim(GDO::escapeS($msg->readString()));
		if ($query === '')
		{
			return $msg->replyBinary($msg->cmd(), '');
		}

		$rooms = LUP_Room::table()->select()
			->where("room_enabled=1 AND room_active=1 AND room_name LIKE '%{$query}%'")
			->order("CASE WHEN room_name = '{$query}' THEN 0 WHEN room_name LIKE '{$query}%' THEN 1 ELSE 2 END, room_sort ASC, room_name ASC")
			->limit(20)
			->uncached()
			->exec();

		$response = '';
		while ($room = $rooms->fetchObject())
		{
			$response .= $this->gdoToBinary($room);
			$response .= $this->gdoToBinary($room->getAddressOrBlank());
			$response .= LUP_Global::userListPayloadData($room, false);
			$response .= $msg->wr32(0);
		}
		$msg->replyBinary($msg->cmd(), $response);
	}
}

GWS_Commands::register(0x1163, new LUPWS_RoomSearch());
