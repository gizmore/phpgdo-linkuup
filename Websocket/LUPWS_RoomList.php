<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Maps\Position;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get a list of rooms within lat/lng.
 *
 * @author gizmore
 */
class LUPWS_RoomList extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$lat = $msg->readFloat();
		$lng = $msg->readFloat();

		if (!Position::isValidLat($lat))
		{
			return $msg->rplyError('err_latitude');
		}

		if (!Position::isValidLng($lng))
		{
			return $msg->rplyError('err_longitude');
		}

		$result = LUP_Room::queryRooms($lat, $lng);
		$response = '';
		while ($room = $result->fetchObject())
		{
			$room instanceof LUP_Room;
			$response .= $this->gdoToBinary($room);
			$response .= $this->gdoToBinary($room->getAddressOrBlank());
			$response .= LUP_Global::userListPayloadData($room, false);
			$response .= $msg->wr32(0);
		}
		$msg->replyBinary($msg->cmd(), $response);
	}

	private function roomUserList(LUP_Room $room)
	{
		$response = '';
		foreach (LUP_Global::$ROOM_USERS[$room->getID()] as $user)
		{
			$response .= GWS_Message::wr32($user->getID());
		}
		$response .= GWS_Message::wr32(0);
		return $response;
	}

}

GWS_Commands::register(0x1101, new LUPWS_RoomList());
