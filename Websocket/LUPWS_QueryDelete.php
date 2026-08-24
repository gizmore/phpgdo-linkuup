<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_QueryThread;
use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Delete all conversation with a user / whole query
 *
 * @author gizmore
 */
class LUPWS_QueryDelete extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		# Params
		$uid = $msg->user()->getID();
		$threadId = $msg->read32u();
		$thread = LUP_QueryThread::table()->findById($threadId);
		if ((!$thread) || (($thread->gdoVar('lupqt_user_a') !== $uid) && ($thread->gdoVar('lupqt_user_b') !== $uid)))
		{
			return $msg->rplyError('err_not_allowed');
		}
		$thread->deleteFor($msg->user());

        # TODO: notify both of deletion.

		# Reply how much went deleted
		$payload = GWS_Message::wr32(1);
		$msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x110C, new LUPWS_QueryDelete());
