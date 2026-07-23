<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_String;
use GDO\User\GDT_User;

final class LUP_HelpRead extends GDO
{

	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('lhr_id'),
			GDT_User::make('lhr_user')->notNull(),
			GDT_String::make('lhr_key')->ascii()->max(64)->notNull(),
		];
	}


}
