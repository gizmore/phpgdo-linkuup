<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_Index;
use GDO\Core\GDT_String;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/** A contiguous private-message conversation, split after one hour of silence. */
final class LUP_QueryThread extends GDO
{
	private const MAX_MESSAGES = 512;
	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('lupqt_id'),
			GDT_User::make('lupqt_user_a')->notNull(),
			GDT_User::make('lupqt_user_b')->notNull(),
			GDT_CreatedAt::make('lupqt_created'),
			GDT_DateTime::make('lupqt_updated')->notNull(),
			GDT_String::make('lupqt_last_text')->max(768)->notNull(),
			GDT_Checkbox::make('lupqt_a_deleted')->notNull()->initial('0'),
			GDT_Checkbox::make('lupqt_b_deleted')->notNull()->initial('0'),
			GDT_Index::make('lupqt_pair_updated')->btree()->indexColumns('lupqt_user_a', 'lupqt_user_b', 'lupqt_updated'),
		];
	}

	public function getID(): ?string { return $this->gdoVar('lupqt_id'); }

	public function otherUserID(GDO_User $user): string
	{
		return $this->gdoVar('lupqt_user_a') === $user->getID() ?
			$this->gdoVar('lupqt_user_b') : $this->gdoVar('lupqt_user_a');
	}

	public function isDeletedFor(GDO_User $user): bool
	{
		$field = $this->gdoVar('lupqt_user_a') === $user->getID() ? 'lupqt_a_deleted' : 'lupqt_b_deleted';
		return $this->gdoVar($field) !== '0';
	}

	public function deleteFor(GDO_User $user): void
	{
		$field = $this->gdoVar('lupqt_user_a') === $user->getID() ? 'lupqt_a_deleted' : 'lupqt_b_deleted';
		$this->saveVar($field, '1');
	}

	public function touch(string $text): void
	{
		$this->saveVars([
			'lupqt_updated' => Time::getDate(),
			'lupqt_last_text' => $text,
			// A new message revives a locally removed conversation for both sides.
			'lupqt_a_deleted' => '0',
			'lupqt_b_deleted' => '0',
		]);
	}

	/** Get the active pair thread or begin a new one after an hour of silence. */
	public static function forMessage(GDO_User $from, GDO_User $to): self
	{
		$a = $from->getID();
		$b = $to->getID();
		if ((int)$a > (int)$b)
		{
			[$a, $b] = [$b, $a];
		}
		$query = self::table()->select('*');
		$query->where("lupqt_user_a=$a AND lupqt_user_b=$b");
		$query->order('lupqt_updated DESC')->limit(1);
		$thread = $query->exec()->fetchObject();
		if ($thread &&
			(Time::getTimestamp($thread->gdoVar('lupqt_updated')) >= (Time::getTimestamp(Time::getDate()) - Time::ONE_HOUR)) &&
			(self::messageCount($thread) < self::MAX_MESSAGES))
		{
			return $thread;
		}
		$now = Time::getDate();
		return self::blank([
			'lupqt_user_a' => $a,
			'lupqt_user_b' => $b,
			'lupqt_updated' => $now,
			'lupqt_last_text' => '',
		])->insert();
	}

	private static function messageCount(self $thread): int
	{
		return (int)LUP_QueryMessage::table()->countWhere("lupqm_thread={$thread->getID()}");
	}
}
