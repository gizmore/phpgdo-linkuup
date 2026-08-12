<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Address\GDO_Address;
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

		// (0,0) is the frontend's explicit discovery-only sentinel when a user
		// has not granted GPS access. It lists public rooms but does not affect
		// chat entry: LUPWS_Join still checks the user's real coordinates.
		$hasPosition = !($lat === 0.0 && $lng === 0.0);
		// A real position is filtered and ordered by its visibility radius. Without
		// one, retain the complete test catalogue so category browsing never makes
		// places appear to have disappeared.
		$result = $hasPosition ? LUP_Room::queryRooms($lat, $lng) : LUP_Room::queryRooms();
		$rooms = $result->fetchAllObjects();
		// Avoid the N+1 address lookup: a normal list contains dozens of rooms,
		// and asking the database once per room delayed the first visible card.
		$addressIds = [];
		foreach ($rooms as $room)
		{
			if ($addressId = (int)$room->gdoVar('room_address'))
			{
				$addressIds[$addressId] = $addressId;
			}
		}
		$addresses = [];
		if ($addressIds)
		{
			$addressResult = GDO_Address::table()->select()
				->where('address_id IN (' . implode(',', $addressIds) . ')')
				->exec();
			while ($address = $addressResult->fetchObject())
			{
				$addresses[$address->getID()] = $address;
			}
		}

		$response = '';
		foreach ($rooms as $room)
		{
			$room instanceof LUP_Room;
			$response .= $this->gdoToBinary($room);
			$addressId = $room->gdoVar('room_address');
			$response .= $this->gdoToBinary($addresses[$addressId] ?? GDO_Address::blank());
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
