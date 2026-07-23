<?php
namespace GDO\LinkUUp;

use GDO\Votes\GDO_VoteTable;

final class LUP_RoomVote extends GDO_VoteTable
{

	public function gdoVoteObjectTable() { return LUP_Room::table(); }

}
