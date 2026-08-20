<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_Room extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		if (!($room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->rplyError('err_room');
		}
		if ($room->isDisabled())
		{
			return $msg->rplyError('err_room_disabled');
		}
		$response = $this->gdoToBinary($room);
		$response .= $this->gdoToBinary($room->getAddressOrBlank());
		$response .= LUP_Global::userListPayloadData($room, false);
		$response .= $msg->wr32(0);
		$msg->replyBinary($msg->cmd(), $response);
	}

	public function hookLUPRoomAdded(LUP_Room $room)
	{
		self::broadcastRoomAdded(LUP_Global::refreshRoom($room->getID()) ?: $room);
	}

	/** A new room is a live catalogue event, not just a successful form submit. */
	public static function broadcastRoomAdded(LUP_Room $room): void
	{
		$command = new self();
		$payload = $command->gdoToBinary($room);
		$payload .= $command->gdoToBinary($room->getAddressOrBlank());
		$payload .= LUP_Global::userListPayloadData($room, false);
		$payload .= GWS_Message::wr32(0);
		GWS_Global::broadcastBinary(GWS_Message::payload(0x1165) . $payload);
	}

}

GWS_Commands::register(0x1102, new LUPWS_Room());
