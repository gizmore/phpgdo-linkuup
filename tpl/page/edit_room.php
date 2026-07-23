<?php

use GDO\Form\GDT_Form;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\Method\EditMenu;

/** @var $room LUP_Room */
/** @var $form GDT_Form * */
echo EditMenu::make()->inputs(['room' => $room->getID()])->execute()->render();
echo $form->render();
