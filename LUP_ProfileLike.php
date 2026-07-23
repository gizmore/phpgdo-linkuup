<?php
namespace GDO\LinkUUp;

use GDO\Votes\GDO_LikeTable;

final class LUP_ProfileLike extends GDO_LikeTable
{

	public function gdoLikeObjectTable() { return LUP_Trophy::table(); }

	public function gdoLikeForGuests() { return Module_LinkUUp::instance()->cfgProfileLikeGuests(); }

	public function gdoMaxLikeCount() { return 2123123123; }

}
