<?php

use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;

$tabs = GDT_Bar::make('tabs')->horizontal();
$tabs->addFields(
	GDT_Link::make('link_rooms')->href(href('LinkUUp', 'Rooms')),
	GDT_Link::make('link_add_room')->href(href('LinkUUp', 'AddRoom'))->icon('add'),
	GDT_Link::make('link_cats')->href(href('LinkUUp', 'CategoryList')),
	GDT_Link::make('link_add_cat')->href(href('LinkUUp', 'CategoryCRUD'))->icon('add'),
);
echo $tabs->render();
