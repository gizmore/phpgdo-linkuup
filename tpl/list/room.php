<?php
namespace GDO\LinkUUp\tpl\list;

use GDO\LinkUUp\LUP_Room;
use GDO\Table\GDT_ListItem;

/**
 * @var LUP_Room $room;
 */
$li = GDT_ListItem::make()->gdo($room);
$li->creatorHeader();
$li->editorFooter();
echo $li->render();
