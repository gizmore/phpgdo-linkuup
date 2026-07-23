<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDO;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

/**
 * Websocket command to perform a user search.
 */
class LUPWS_UserSearch extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		if (!$msg->user()->isMember())
		{
			return $msg->rplyError('err_members_only');
		}

		$q = GDO::escapeS($msg->readString());
		$result = $query->exec();
        $condition = sprintf('user_type IN ("guest", "member") AND (user_name LIKE \'%%%1$s%%\' OR user_guest_name LIKE \'%%%1$s%%\')', $q);
        $order = sprintf(
            'CASE
                    WHEN user_name = \'%1$s\' OR user_guest_name = \'%1$s\' THEN 0
                    WHEN user_name LIKE \'%1$s%%\' OR user_guest_name LIKE \'%1$s%%\' THEN 1
                    ELSE 2
                END ASC,
                LEAST(
                    COALESCE(LENGTH(user_name), 999999),
                    COALESCE(LENGTH(user_guest_name), 999999)
                ) ASC
                user_name ASC',
            $q
        );
        $query = GDO_User::table()
            ->select('user_id')
            ->where($condition)
            ->order($order)
            ->limit(20)
            ->uncached();
        $result = $query->exec();

        $response = '';
		while ($userId = $result->fetchVar())
		{
			if ($user = GWS_Global::getOrLoadUserById($userId))
			{
				$response .= LUP_Global::fullUserPayload($user);
			}
		}
		$msg->replyBinary($msg->cmd(), $response);
	}

}

GWS_Commands::register(0x1161, new LUPWS_UserSearch());
