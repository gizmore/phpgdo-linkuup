<?php

use GDO\Form\GDT_Form;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomWorker;
use GDO\LinkUUp\LUP_RoomWorkerActivation;
use GDO\LinkUUp\Method\EditMenu;
use GDO\Mail\GDT_Email;
use GDO\Table\GDT_Table;
use GDO\User\GDT_Username;

echo EditMenu::make()->inputs(['room' => $room->getID()])->execute()->render();

/**
 * @var $room LUP_Room
 */
if ($room)
{
	$result = LUP_RoomWorker::table()->getCoworkersResult($room);
	$table = GDT_Table::make();
	$table->title('lup_room_workers', [
		$result->numRows(), $room->gdoDisplay('room_name'),
		$room->displayAddress()]);
	$table->addHeaderFields(
		GDT_Username::make('user_name'),
		GDT_Email::make('user_email'),
	);
	$table->fetchAs(LUP_RoomWorker::table());
	$table->result($result);
	echo $table->renderHTML();

	# Render Activations if any
	$result = LUP_RoomWorkerActivation::table()->getCoworkersResult($room);
	$table = GDT_Table::make()->hideEmpty();
	$table->title('lup_room_workers_invited', [$result->numRows(), $room->gdoDisplay('room_name')]);
	$table->addHeaderFields(
		GDT_Email::make('lrwa_email'),
	);
	$table->fetchAs(LUP_RoomWorkerActivation::table());
	$table->result($result);
	echo $table->renderHTML();
}


/**
 * @var $form GDT_Form
 */
echo $form->render();
