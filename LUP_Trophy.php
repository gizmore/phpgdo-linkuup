<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Int;
use GDO\Core\GDT_String;
use GDO\User\GDO_User;
use GDO\User\GDT_User;
use GDO\Votes\WithLikes;

final class LUP_Trophy extends GDO
{

	use WithLikes;

	public static function getOrCreate(GDO_User $user)
	{
		if (!($trophy = self::get($user)))
		{
			$trophy = self::create($user);
		}
		return $trophy;
	}

	###########
	### GDO ###
	###########

	private static function get(GDO_User $user)
	{
		return self::table()->getById($user->getID());
	}

	##############
	### Getter ###
	##############

	private static function create(GDO_User $user)
	{
		return self::blank(['lt_uid' => $user->getID()])->insert();
	}

	public function gdoLikeTable() { return LUP_ProfileLike::table(); }

	public function gdoColumns(): array
	{
		return [
			GDT_User::make('lt_uid')->primary(),
			GDT_String::make('lt_status'),
			# v1 and working
			GDT_Checkbox::make('lt_vip')->notNull()->initial('0'),
			GDT_Int::make('lt_chat_sent')->notNull()->unsigned()->initial('0'),
			GDT_Int::make('lt_query_sent')->notNull()->unsigned()->initial('0'),
			GDT_Int::make('lt_query_received')->notNull()->unsigned()->initial('0'),
			GDT_Int::make('lt_visits')->notNull()->unsigned()->initial('0'),
		];
	}

	public function getStatus() { return $this->gdoVar('lt_status'); }

	public function isVIP() { return $this->gdoVar('lt_vip') > 0; }

	public function getChatSent() { return $this->gdoVar('lt_chat_sent'); }

	###############
	### Factory ###
	###############

	public function getQuerySent() { return $this->gdoVar('lt_query_sent'); }

	public function getQueryRecieved() { return $this->gdoVar('lt_query_received'); }

	public function getVisits() { return $this->gdoVar('lt_visits'); }

	###############
	### Counter ###
	###############

	public function countUp($field)
	{
		return $this->increase($field);
	}

}
