<?php
namespace GDO\LinkUUp\Method;

use GDO\Comments\Method\Edit;
use GDO\Core\GDT;
use GDO\Core\GDT_Tuple;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_RoomComments;

/**
 * Add menu to Edit function.
 *
 * @author gizmore
 */
final class EditComment extends Edit
{

	public function isTrivial(): bool { return false; }

	public function execute(): GDT
	{
		$result = parent::execute();
		$menu = $this->templatePHP('crumb/edit_menu.php', ['room' => $this->getRoom()]);
		return GDT_Tuple::makeWith($menu, $result);
	}

	public function getRoom(): LUP_Room
	{
		return LUP_RoomComments::getCommentedObjectByCommentS($this->comment, LUP_Room::table());
	}

	public function hrefBack()
	{
		return href('LinkUUp', 'RoomComments', "&room={$this->getRoom()->getID()}");
	}

}
