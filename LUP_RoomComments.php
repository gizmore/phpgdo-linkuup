<?php
namespace GDO\LinkUUp;

use GDO\Comments\GDO_Comment;
use GDO\Comments\GDO_CommentTable;
use GDO\Core\GDO;
use GDO\User\GDO_User;

final class LUP_RoomComments extends GDO_CommentTable
{

	public function gdoCommentedObjectTable(): GDO { return LUP_Room::table(); }

	public function gdoAllowFiles(): bool { return false; }

	public function gdoEnabled(): bool { return true; }

	public function gdoMaxComments(GDO_User $user): int { return 1; }

// 	/**
// 	 * @return LUP_Room
// 	 */
// 	public function getRoom() { return $this->getCommentedObject(); }

	public function canDeleteComment(GDO_Comment $comment, GDO_User $user): bool
	{
		$room = self::getCommentedObjectByCommentS($comment, LUP_Room::table());
		return $room->isOwner($user);
	}

	public function href_edit()
	{
		return href('LinkUUp', 'EditComment', "&id={$this->gdoVar('comment_id')}");
	}

}
