<?php
namespace GDO\LinkUUp\Method;

use GDO\Address\GDO_Address;
use GDO\Core\GDO;
use GDO\Core\GDT_Hook;
use GDO\Form\GDT_Form;
use GDO\Form\MethodCrud;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomWorker;
use GDO\UI\GDT_Button;
use GDO\User\GDO_UserPermission;

final class AddRoom extends MethodCrud
{

	public function isTrivial(): bool { return false; }

	public function getPermission(): ?string { return 'staff'; }

	public function hrefList(): string { return href('LinkUUp', 'Rooms'); }

	public function gdoTable(): GDO { return LUP_Room::table(); }

	public function createFormButtons(GDT_Form $form): void
	{
		$form->removeFieldNamed('room_address');
		if ($this->crudMode === self::CREATED)
		{
			$table = GDO_Address::table();
			$form->addFields(
				$table->gdoColumn('address_country'),
				$table->gdoColumn('address_zip'),
				$table->gdoColumn('address_city'),
				$table->gdoColumn('address_street'),
			);
		}

		elseif ($this->crudMode === self::EDITED)
		{
			$form->getField('room_image')->previewHREF(href('LinkUUp', 'RoomImage', '&id=' . $this->gdo->getID() . '&file={id}'));
			$form->addFields(
				GDT_Button::make('edit_addr')->href($this->address()->href_edit()),
			);
		}
		parent::createFormButtons($form);
	}

	/**
	 * @return GDO_Address
	 */
	public function address() { return $this->room()->getAddress(); }

	/**
	 * @return LUP_Room
	 */
	public function room() { return $this->gdo; }

	public function afterCreate(GDT_Form $form, GDO $gdo): void
	{
		$address = GDO_Address::blank($form->getFormVars());
		if (!$address->emptyAddress())
		{
			$address->insert();
			$gdo->saveVar('room_address', $address->getID());
		}
		$this->updateOwnerPermissions($gdo);
		GDT_Hook::callWithIPC('LUPRoomAdded', $gdo);
	}

	public function updateOwnerPermissions(LUP_Room $room)
	{
		if ($owner = $room->getOwner())
		{
			LUP_RoomWorker::addWorker($room, $owner);
			GDO_UserPermission::grant($owner, 'lup_owner');
		}
	}

	public function afterUpdate(GDT_Form $form, GDO $gdo): void
	{
		$this->updateOwnerPermissions($gdo);
	}

}
