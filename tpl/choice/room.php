<?php
namespace GDO\LinkUUp\tpl\choice;

use GDO\LinkUUp\LUP_Room;

/**
 * @var $room LUP_Room
 */
if (($address = $room->getAddress()) && (!$address->emptyAddress()))
{
	printf('%s, %s %s', $room->gdoDisplay('room_name'), $address->gdoDisplay('address_street'), $address->gdoDisplay('address_city'));
}
else
{
	printf('%s', $room->gdoDisplay('room_name'));
}
