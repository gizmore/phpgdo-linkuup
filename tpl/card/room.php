<?php

use GDO\LinkUUp\LUP_Room;
use GDO\User\GDO_User;

$room instanceof LUP_Room;
$user = GDO_User::current(); ?>
<md-card class="lup-room">
    <md-card-title>
        <md-card-title-text>
	  <span class="md-headline">
		<div>“<?=html($room->getName());?>”</div>
	  </span>
        </md-card-title-text>
    </md-card-title>
    <gdo-div></gdo-div>
    <md-card-content flex>
    </md-card-content>
    <gdo-div></gdo-div>
    <md-card-actions layout="row" layout-align="end center">
    </md-card-actions>
</md-card>
