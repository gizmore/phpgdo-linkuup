<?php

use GDO\LinkUUp\LUP_Room;
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;

/**
 * @var LUP_Room $room
 */

$bar = GDT_Bar::make('bar_links')->horizontal();

$bar->addField(GDT_Link::make('link_edit_room')->href($room->href_edit()));
$bar->addField(GDT_Link::make('link_edit_room_workers')->href($room->href_coworkers()));
$bar->addField(GDT_Link::make('link_edit_room_comments')->href($room->href_comments()));

echo $bar->render();
