<?php

use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Link;
use GDO\User\GDO_User;

$navbar instanceof GDT_Bar;
$user = GDO_User::current();
if ($user->isStaff())
{
	$navbar->addField(GDT_Link::make('link_lup')->href(href('LinkUUp', 'Main')));
}
