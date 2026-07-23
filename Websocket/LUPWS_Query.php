<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUP_QueryMessage;
use GDO\LinkUUp\LUPWS_Command;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

/**
 * Send a private message to a user.
 *
 * @author gizmore
 */
class LUPWS_Query extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();

		if ((!$user->isMember()) && (!$this->allowGuest()))
		{
			return $msg->rplyError('err_user_required');
		}

		$toId = $msg->read32();
		$text = $msg->readString();
		$offline = false;
// 		$now = Time::getTimestamp();
// 		$nowDate = Time::getDate($now);

		if (!($to = GWS_Global::getUserByID($toId)))
		{
			if (!($to = GDO_User::table()->getById($toId)))
			{
				return $msg->rplyError('err_user');
			}
			$offline = true;
		}

		if ((!$to->isMember()) && (!$this->allowGuest()))
		{
			return $msg->rplyError('err_lup_no_guest_can_receive_pm');
		}

		if ((!LUP_Global::isVIP($user)) && (!LUP_Global::isVIP($to)))
		{
			if (!Module_LinkUUp::instance()->cfgOpenQuery())
			{
				if (!LUP_Global::userSeesUser($to, $user))
				{
					return $msg->rplyError('err_user_not_near');
				}
			}
		}

		# TODO: CHECK FRIENDLIST STATE 1

		# Trophy statistics
		if ($trophy = LUP_Global::trophyForUser($user))
		{
			$trophy->countUp('lt_query_sent');
		}

// 		if (!$offline)
		{
			if ($trophy = LUP_Global::trophyForUser($to))
			{
				$trophy->countUp('lt_query_received');
			}
		}

		$qmsg = LUP_QueryMessage::blank([
			'lupqm_id' => '0',
			'lupqm_from' => $user->getID(),
			'lupqm_to' => $to->getID(),
			'lupqm_text' => $text,
// 			'lupqm_created' => $nowDate,
			'lupqm_delivered' => null,
			'lupqm_read' => null,
			'lupqm_ack' => '0',
		])->insert();

		if (!$offline)
		{
			$qmsg->deliver();
		}

		$msg->replyBinary($msg->cmd(), $qmsg->payload());
	}

	private function allowGuest()
	{
		return Module_LinkUUp::instance()->cfgAllowGuestQuery();
	}

}

GWS_Commands::register(0x1108, new LUPWS_Query());
