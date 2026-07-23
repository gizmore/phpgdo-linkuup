<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_QueryMessage;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Update and retrieve read/deliver status.
 *
 * @author gizmore
 */
class LUPWS_QueryRead extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$qmsg = LUP_QueryMessage::table()->findById($msg->read32());

		if ($qmsg->isTo($msg->user()))
		{
			if (!$qmsg->isRead())
			{
				$qmsg->markRead();
			}
		}
		elseif ($qmsg->isFrom($msg->user()))
		{
			if ($qmsg->isRead())
			{
				$qmsg->acknowledge();
			}
		}
		else
		{
			return $msg->rplyError('err_not_allowed', [t('not_involved')]);
		}

		return $msg->replyBinary($msg->cmd(), $qmsg->payloadStatus());
	}

}

GWS_Commands::register(0x1109, new LUPWS_QueryRead());
