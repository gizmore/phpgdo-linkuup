<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Cuddle;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;
use GDO\Websocket\Server\GWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * List the confirmed Cuddles of one profile, subject to that profile's ACL.
 */
final class LUPWS_UserCuddles extends GWS_Command
{
    public function execute(GWS_Message $msg)
    {
        $user = GDO_User::findById((string)$msg->read32u());

        /** @var GDT_ACLRelation $acl */
        $acl = Module_LinkUUp::instance()->userSetting($user, 'lup_cuddles_visible');
        $reason = '';
        if (!$acl->hasAccess($msg->user(), $user, $reason))
        {
            return $msg->replyErrorMessage($msg->cmd(), t('err_not_allowed', [$reason]));
        }

        $userId = (int)$user->getID();
        $result = LUP_Cuddle::table()->
            select('cuddle_a, cuddle_b, cuddle_day')->
            where("cuddle_a=$userId OR cuddle_b=$userId") ->
            order('cuddle_day DESC, cuddle_id DESC')->
            exec();

        $payload = '';
        while ($row = $result->fetchAssoc())
        {
            $partnerId = ((int)$row['cuddle_a'] === $userId) ? $row['cuddle_b'] : $row['cuddle_a'];
            $day = strtotime($row['cuddle_day'] . ' UTC');
            $payload .= GWS_Message::wr32($partnerId);
            $payload .= GWS_Message::wr32($day ?: 0);
        }

        return $msg->replyBinary($msg->cmd(), $payload);
    }
}

GWS_Commands::register(0x1164, new LUPWS_UserCuddles());
