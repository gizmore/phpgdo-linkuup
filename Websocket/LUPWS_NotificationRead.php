<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Date\Time;
use GDO\LinkUUp\LUP_Notification;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Mark a notification as read.
 *
 * @author gizmore
 */
class LUPWS_NotificationRead extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		# Load
		$notificationId = $msg->read32u(); # id is the next 4 byte from websocket
		$table = LUP_Notification::table(); # DB Model
		$notification = $table->findById($notificationId); # fetch errors should be handled by LUPWS_Command

		# Permission check
		if ($notification->getUserID() !== $msg->user()->getID())
		{
			return $msg->rplyError('err_notification');
		}

		# Double mark read for the lulz (does not matter)
// 		if ($notification->isRead())
// 		{
// 			return $msg->rplyError('err_notification_read');
// 		}

		# Save DB
		$notification->saveVar('note_read', Time::getDate());

		# Reply sync
		$msg->replyBinary($msg->cmd(), $msg->wr32($notificationId));
	}

}

GWS_Commands::register(0x1142, new LUPWS_NotificationRead());
