<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Index;
use GDO\Core\GDT_JSON;
use GDO\Date\GDT_DateTime;
use GDO\User\GDT_User;

/**
 * To handle all different notifications, we json encode the notification data.
 *
 * Current Notifications:
 * {type:join, user:id, room:id} => user joined room
 * {type:friend, user:id, friend:id} => user and friend are now friends
 * {type:nofriends, user:id, friend:id} => user and friend are not friends anymore
 *
 * @version 6.07
 * @author gizmore
 */
final class LUP_Notification extends GDO
{

	###########
	### GDO ###
	###########
	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('note_id'),
			GDT_User::make('note_user')->notNull(),
			GDT_JSON::make('note_data')->notNull(),
			GDT_CreatedAt::make('note_created'),
			GDT_DateTime::make('note_read'),
			GDT_Index::make('note_index_user')->indexColumns('note_user'),
		];
	}

	##############
	### Getter ###
	##############
	public function getUserID() { return $this->gdoVar('note_user'); }

	public function isRead() { return $this->gdoVar('note_read') !== null; }

}
