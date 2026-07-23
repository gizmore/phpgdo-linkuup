<?php

use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;

$bar = GDT_Bar::make()->horizontal();

$bar->addField(GDT_Link::make('link_add_room')->href(href('LinkUUp', 'AddRoom')));

echo $bar->render();
