<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Is user course visible for you?
 *
 * @author gizmore@wechall.net
 */
final class LUPWS_UserCourseAllowed extends GWS_Command
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

		return $msg->replyBinary($msg->cmd());
	}

}

# Register in websocket handler
GWS_Commands::register(0x1162, new LUPWS_UserCourseAllowed());
