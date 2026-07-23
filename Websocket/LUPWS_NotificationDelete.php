<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Notification;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Delete a notification.
 *
 * @author gizmore
 */
class LUPWS_NotificationDelete extends LUPWS_Command
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

		# Delete
		$notification->delete();

		# Reply sync
		$msg->replyBinary($msg->cmd(), $msg->wr32($notificationId));
	}

}

GWS_Commands::register(0x1144, new LUPWS_NotificationDelete());
