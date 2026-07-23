<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_Object;
use GDO\Date\Time;
use GDO\DB\Result;
use GDO\Mail\GDT_Email;
use GDO\User\GDO_User;

/**
 * This table holds invite assignments after invitation mail activates.
 *
 * @version 7.0.3
 * @since 6.0.9
 * @author gizmore
 */
final class LUP_RoomWorkerActivation extends GDO
{

	public function gdoColumns(): array
	{
		return [
			GDT_Email::make('lrwa_email')->notNull()->primary(),
			GDT_Object::make('lrwa_room')->table(LUP_Room::table())->notNull()->cascade()->primary(),
			GDT_CreatedAt::make('lrwa_created'),
			GDT_CreatedBy::make('lrwa_creator'),
		];
	}

	public function getCreated(): string { return $this->gdoVar('lrwa_created'); }

	/**
	 * Get Coworkers for a room.
	 *
	 * @return GDO_User[]
	 */
	public function getCoworkers(LUP_Room $room): array
	{
		return $this->getCoworkersResult($room)->fetchAllObjects();
	}

	/**
	 * Get Coworkers Query Result for a room.
	 */
	public function getCoworkersResult(LUP_Room $room): Result
	{
		$query = $this->select('*');
		$query->where("lrwa_room={$room->getID()}");
		return $query->exec();
	}

	#################
	### Coworkers ###
	#################

	public function hookInviteCompleted(GDO_User $user): void
	{
		$invites = $this->getActivations($user);
		foreach ($invites as $invite)
		{
			$invite->activate($user);
		}
	}

	/**
	 * @return self[]
	 */
	public function getActivations(GDO_User $user = null): array
	{
		$query = self::table()->select();
		if ($user)
		{
			$query->where("lrwa_email='{$user->getMail()}'");
		}
		return $query->exec()->fetchAllObjects();
	}

	###################
	### Activations ###
	###################

	public function activate(GDO_User $user): void
	{
		LUP_RoomWorker::blank([
			'lrw_room' => $this->getRoomID(),
			'lrw_user' => $user->getID(),
			'lrw_created' => Time::getDate(),
			'lrw_creator' => $this->getCreatorID(),
		])->insert();
	}

	public function getRoomID(): string { return $this->gdoVar('lrwa_room'); }

	public function getCreatorID(): string { return $this->gdoVar('lrwa_creator'); }

}
