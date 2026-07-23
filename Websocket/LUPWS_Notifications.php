<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDO;
use GDO\Date\Time;
use GDO\LinkUUp\LUP_Notification;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get notifications for a user.
 *
 * @author gizmore
 */
class LUPWS_Notifications extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$uid = $msg->user()->getID();
		$time = $msg->read32u();
		$table = LUP_Notification::table();

		$count = $table->countWhere("note_user=$uid");

		$query = $table->select('*');
		$query->where('note_user=' . $uid);
		if ($time)
		{
			$cut = Time::getDate($time);
			$query->where('note_created<=' . GDO::quoteS($cut));
		}
		$query->order('note_created DESC');
		$query->limit(21);
		$result = $query->exec();

		$repsonse = '';
		$repsonse .= $msg->wr32($count);

		while ($notification = $table->fetch($result))
		{
			$repsonse .= $this->gdoToBinary($notification);
		}
		$msg->replyBinary($msg->cmd(), $repsonse);
	}

}

GWS_Commands::register(0x1141, new LUPWS_Notifications());
