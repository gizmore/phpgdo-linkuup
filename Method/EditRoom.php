<?php
namespace GDO\LinkUUp\Method;

use GDO\Address\GDO_Address;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\MethodForm;
use GDO\LinkUUp\LUP_Room;
use GDO\User\GDO_User;

final class EditRoom extends MethodForm
{

	/**
	 * @var LUP_Room
	 */
	private $room = null;

	public function isTrivial(): bool { return false; }

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('room')->table(LUP_Room::table())->notNull(),
		];
	}

	public function renderPage(): GDT
	{
		$tVars = [
			'room' => $this->getRoom(),
			'form' => $this->getForm(),
		];
		return $this->templatePHP('page/edit_room.php', $tVars);
	}

	public function hasPermission(GDO_User $user, string &$error, array &$args): bool
	{
		$room = $this->getRoom();
		return $room->canEdit($user);
	}


	/**
	 * @return LUP_Room
	 */
	public function getRoom(): LUP_Room
	{
        if (!isset($this->room))
        {
            $this->room = $this->gdoParameterValue('room');
        }
        return $this->room;
	}

	public function getMethodTitle(): string
	{
		$room = $this->getRoom();
		return t('mt_linkuup_editroom', [html($room->getName())]);
	}

	protected function createForm(GDT_Form $form): void
	{
		$user = GDO_User::current();
		$room = $this->getRoom();

		$staff = $user->isStaff();
		$owner = $room->isOwner($user);
		if ($staff)
		{
			$form->addField($room->gdoColumn('room_owner'));
		}

		if ($owner)
		{
			$form->addField($room->gdoColumn('room_name'));
			$form->addField($room->gdoColumn('room_enabled'));
		}
		$form->addField($room->gdoColumn('room_info'));

		$form->addField($room->gdoColumn('room_color'));

		if ($staff)
		{
			$form->addField($room->gdoColumn('room_category'));
			$form->addField($room->gdoColumn('room_pos'));
			$form->addField($room->gdoColumn('room_view'));
			$form->addField($room->gdoColumn('room_radius'));
			$form->addField($room->gdoColumn('room_show_distance'));
		}

		$form->addField($room->gdoColumn('room_www'));
		$form->addField($room->gdoColumn('room_phone'));
		$form->addField($room->gdoColumn('room_hours'));

		$address = $room->getAddress();
		$address = $address ?: GDO_Address::blank();
		$form->addField($address->gdoColumn('address_country'));
		$form->addField($address->gdoColumn('address_zip'));
		$form->addField($address->gdoColumn('address_city'));
		$form->addField($address->gdoColumn('address_street'));

		if ($owner)
		{
            $form->addField($room->gdoColumn('room_icon')->previewHREF(href('LinkUUp', 'RoomIcon', '&id=' . $room->getID() . '&file={id}')));
            $form->addField($room->gdoColumn('room_image')->previewHREF(href('LinkUUp', 'RoomImage', '&id=' . $room->getID() . '&file={id}')));
		}

		$form->addField(GDT_AntiCSRF::make());

		$form->actions()->addField(GDT_Submit::make());
	}

	public function formValidated(GDT_Form $form): GDT
	{
		$room = $this->getRoom();
		$room->saveVars($form->getFormVars());

		if ($address = $room->getAddress())
		{
			$address->saveVars($form->getFormVars());
		}
		else
		{
			$address = GDO_Address::blank($form->getFormVars());
			if (!$address->emptyAddress())
			{
				$address->insert();
				$room->saveVar('room_address', $address->getID());
			}
		}

		return $this->message('msg_crud_updated', [$room->gdoHumanName()])->addField($this->renderPage());
	}

}
