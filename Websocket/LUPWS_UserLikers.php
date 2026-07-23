<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_ProfileLike;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * WebSocket command to display list of likers.
 *
 * @author gizmore
 */
class LUPWS_UserLikers extends GWS_Command
{

	public function execute(GWS_Message $msg)
	{
		$userid = $msg->read32u();
		$user = GDO_User::findById($userid); # user exist?

		$page = $msg->read16u();
		$_REQUEST['f']['page'] = $page; # Hack

		# Query with page
		$query = LUP_ProfileLike::table()->select('like_user, COUNT(*) c')->group('like_user');
		$query->where("like_object=$userid");
		$query->order('c DESC');
// 		$pagemenu = GDT_PageMenu::make('page')->query($query)->filterQuery($query);
		$result = $query->exec();

		# Payload is pagemenu + all user ids
		$payload = $msg->wr32($user->getID());
// 		$payload .= $this->pagemenuToBinary($pagemenu);
		while ($row = $result->fetchRow())
		{
			if ($row[0])
			{
				$payload .= $msg->wr32($row[0]);
				$payload .= $msg->wr32($row[1]);
			}
		}

		$msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x1133, new LUPWS_UserLikers());
