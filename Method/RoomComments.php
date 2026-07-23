<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDO;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Object;
use GDO\DB\Query;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomComments;
use GDO\Table\MethodQueryTable;
use GDO\UI\GDT_EditButton;
use GDO\UI\GDT_Message;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * List comments for a room.
 *
 * @author gizmore
 *
 */
final class RoomComments extends MethodQueryTable
{

	public function hasPermission(GDO_User $user, string &$error, array &$args): bool
	{
		return $this->getRoom()->canEdit($user);
	}

	/**
	 * @return LUP_Room
	 */
	public function getRoom()
	{
		return $this->gdoParameterValue('room');
	}

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('room')->table(LUP_Room::table())->notNull(),
		];
	}

	public function getQuery(): Query
	{
		$room = $this->getRoom();
		$query = LUP_RoomComments::table()->select('comment_id_t.*');
		$query->joinObject('comment_id');
		$query->joinObject('comment_object');
		$query->join('LEFT JOIN gdo_user ON gdo_user.user_id=comment_creator');
		$query->where("room_id={$room->getID()}");
		if (!GDO_User::current()->isStaff())
		{
			$query->where('comment_deleted IS NULL');
		}
		return $query;
	}

	public function onRenderTabs(): void
	{
// 		return $this->templatePHP('crumb/edit_menu.php', ['room' => $this->getRoom()]);
	}

	public function gdoHeaders(): array
	{
		return [
			GDT_EditButton::make(),
			GDT_CreatedAt::make('comment_created'),
			GDT_User::make('comment_creator'),
			GDT_Message::make('comment_message'),
		];
	}

	public function gdoTable(): GDO { return LUP_RoomComments::table(); }

}
