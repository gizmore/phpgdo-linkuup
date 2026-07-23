<?php
namespace GDO\LinkUUp;

use GDO\Core\GDT_ObjectSelect;
use GDO\User\GDO_User;

/**
 * A select for rooms.
 * Offers "editable" and "enabled" filters.
 *
 * @author gizmore
 */
final class GDT_RoomSelect extends GDT_ObjectSelect
{

	public bool $editableRooms = false;
	public bool $enabledRooms = false;

	#####################
	### Only editable ###
	#####################

	public function __construct()
	{
		parent::__construct();
		$this->table(LUP_Room::table());
	}

	public function gdtDefaultLabel(): ?string
    { return 'room_name'; }

	####################
	### Only enabled ###
	####################

	/**
	 * Init select choices. Respect editable and enabled options.
	 */
	protected function getChoices(): array
	{
		return $this->queryRooms();
	}

	/**
	 * @return LUP_Room[]
	 */
	public function queryRooms(): array
	{
		# Filter conditions
		$where = [];

		# only editable for you
		if ($this->editableRooms)
		{
			# Only needs condition for non staff
			$user = GDO_User::current();
			if (!$user->isStaff())
			{
				$coworker_query = "SELECT lrw_room FROM lup_roomworker WHERE lrw_user = {$user->getID()}";
				$where[] = "(room_owner = {$user->getID()} OR room_id IN ( $coworker_query ))";
			}
		}

		# Only enabled rooms?
		if ($this->enabledRooms)
		{
			$where[] = '(room_enabled=1)';
		}

		# Query rooms
		return empty($where) ? $this->table->allCached() : $this->table->allWhere(implode(' AND ', $where));
	}

	/**
	 * Only show editable rooms.
	 *
	 * @param bool $editableRooms
	 *
	 * @return self
	 */
	public function editableRooms(bool $editableRooms = true): self
	{
		$this->editableRooms = $editableRooms;
		return $this;
	}

	/**
	 * Only show enabled rooms
	 */
	public function enabledRooms(bool $enabledRooms = true): self
	{
		$this->enabledRooms = $enabledRooms;
		return $this;
	}

}
