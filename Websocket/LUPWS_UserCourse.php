<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Date\Time;
use GDO\LinkUUp\LUP_RoomVisit;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get an overview of the visited locations for a user.
 *
 * @author gizmore@wechall.net
 */
final class LUPWS_UserCourse extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		# User param
		$user = GDO_User::findById($msg->read32u());

		/**
		 * @var GDT_ACLRelation $acl
		 */
		$acl = Module_LinkUUp::instance()->userSetting($user, 'lup_course_visible');
		$reason = '';
		if (!$acl->hasAccess($msg->user(), $user, $reason))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_not_allowed', [$reason]));
		}

		$query = LUP_RoomVisit::table()->
		select('COUNT(*) visit_count, MAX(visit_at) visit_last, visit_room');
		$query->where('visit_user=' . $user->getID());
		$query->group('visit_room');
		$query->order('visit_count DESC');

		$result = $query->exec();

		$payload = '';
		while ($row = $result->fetchAssoc())
		{
			if ($row['visit_count'])
			{
				$payload .= GWS_Message::wr32($row['visit_room']);
				$payload .= GWS_Message::wr32($row['visit_count']);
				$payload .= GWS_Message::wr32(Time::getTimestamp($row['visit_last']));
			}
		}

		$msg->replyBinary($msg->cmd(), $payload);
	}

}

# Register in websocket handler
GWS_Commands::register(0x1160, new LUPWS_UserCourse());
