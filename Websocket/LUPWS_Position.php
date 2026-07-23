<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Update a users geoposition.
 * Will send part messages when out of range.
 * Will insert a signup GPS on first call.
 */
final class LUPWS_Position extends LUPWS_Command
{

	public function execute(GWS_Message $message)
	{
		$user = $message->user();
		$lat = $message->readFloat();
		$lng = $message->readFloat();

		LUP_Global::updateGPS($user, $lat, $lng);
// 		LUP_SignupGPS::updateGPS($user, $lat, $lng);

		# Make user part rooms when not in range anymore
		$rooms = LUP_Global::getRoomsForUser($user);
		foreach ($rooms as $room)
		{
			if (!LUP_Global::isVIP($user))
			{
				if (!$room->isInChatRange($lat, $lng))
				{
					LUP_Global::part($room, $user);
				}
			}
		}
	}

}

GWS_Commands::register(0x1112, new LUPWS_Position());
