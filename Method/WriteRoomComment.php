<?php
namespace GDO\LinkUUp\Method;

use GDO\Comments\Comments_Write;
use GDO\Comments\GDO_CommentTable;
use GDO\LinkUUp\LUP_RoomComments;

/**
 * CommentWrite adapter for room comments.
 *
 * @author gizmore
 */
final class WriteRoomComment extends Comments_Write
{

	public function hrefList(): string { return href('LinkUUp', 'Rooms'); }

	public function gdoCommentsTable(): GDO_CommentTable { return LUP_RoomComments::table(); }

}
