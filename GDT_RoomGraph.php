<?php
namespace GDO\LinkUUp;

use GDO\JPGraph\GDT_GraphSelect;

class GDT_RoomGraph extends GDT_GraphSelect
{

	public LUP_Room $room;

	public function room(LUP_Room $room): self
	{
		$this->room = $room;
		return $this;
	}

}
