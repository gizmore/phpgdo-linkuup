<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_Validator;
use GDO\Form\MethodForm;
use GDO\Invite\GDO_Invitation;
use GDO\Invite\Method\Form;
use GDO\LinkUUp\GDT_RoomSelect;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomWorker;
use GDO\LinkUUp\LUP_RoomWorkerActivation;
use GDO\Mail\GDT_Email;
use GDO\UI\GDT_Divider;
use GDO\UI\GDT_Paragraph;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * @version 6.11.0
 * @since 6.10.3
 * @author gizmore
 */
final class AddCoworker extends MethodForm
{

	private LUP_Room $room;

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('room')->table(LUP_Room::table())->notNull(),
		];
	}

	protected function createForm(GDT_Form $form): void
	{
		$room = $this->getRoom();
		$form->addField(GDT_RoomSelect::make('room')->initial($room ? $room->getID() : '0')->editableRooms()->notNull());
		$form->addField(GDT_Divider::make('d1'));
		$form->addField(GDT_Paragraph::make('p1')->text('lup_add_coworker_by_name'));
		$user = GDT_User::make('user');
		$form->addField($user);
		$form->addField(GDT_Divider::make('d2'));
		$form->addField(GDT_Paragraph::make('p2')->text('lup_add_coworker_by_email'));
		$form->addField(GDT_Email::make('email'));
		$form->addField(GDT_AntiCSRF::make());
		$form->addField(GDT_Validator::make()->validator($form, $user, [$this, 'validateOnlyOne']));
		$form->actions()->addField(GDT_Submit::make());
	}

	public function getRoom(): LUP_Room
	{
		if (!isset($this->room))
		{
			$this->room = $this->gdoParameterValue('room');
		}
		return $this->room;
	}

	public function validateOnlyOne(GDT_Form $form, GDT $field, $value)
	{
		$email = $form->getFormValue('email');
		$user = $form->getFormValue('user');
		if ((!$email) === (!$user))
		{
			$form->getField('email')->error('err_lup_validate_exactly_one');
			$field->error('err_lup_validate_exactly_one');
			return false;
		}
		return true;
	}

	public function getCoworkers()
	{
		$room = $this->getRoom();
		return $room ? $room->getCoworkers() : [];
	}


	public function renderPage(): GDT
	{
		$tVars = [
			'room' => $this->getRoom(),
			'form' => $this->getForm(),
			'coworkers' => $this->getCoworkers(),
		];
		return $this->templatePHP('page/add_coworker.php', $tVars);
	}

	public function formValidated(GDT_Form $form): GDT
	{
		# Found user, simply add him.
		if (
			($user = $form->getFormValue('user')) ||
			(($email = $form->getFormVar('email')) && ($user = GDO_User::table()->getBy('user_email', $email)))
		)
		{
			$this->resetForm();
			LUP_RoomWorker::addWorker($this->getRoom(), $user);
			return $this->message('msg_form_saved')->addField($this->renderPage());
		}

		if ($email = $form->getFormVar('email'))
		{
			$this->resetForm();
			if (!GDO_Invitation::getBy('invite_email', $email))
			{
				return $this->sendInvitation($email)->addField($this->renderPage());
			}

			return $this->addActivation($email, $this->getRoom())->addField($this->renderPage());
		}
	}

	private function sendInvitation($email)
	{
		$form = Form::make();
		$response = $form->validateInvitationMessage($email);
		if (!$response->isError())
		{
			$form->sendInvitationMail(GDO_User::current(), $email);
		}

		$response->addField($this->addActivation($email, $this->getRoom()));
		return $response;
	}

	private function addActivation($email, LUP_Room $room)
	{
		LUP_RoomWorkerActivation::blank([
			'lrwa_email' => $email,
			'lrwa_room' => $room->getID(),
		])->replace();
		return $this->message('msg_form_saved');
	}

}
