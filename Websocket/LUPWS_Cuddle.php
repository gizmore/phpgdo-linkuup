<?php
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUP_Cuddle;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Maps\GDT_Position;
use GDO\Maps\Position;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_Cuddle extends LUPWS_Command
{
    public function execute(GWS_Message $msg)
    {
        $a = $msg->user();
        $b = GDO_User::getById((string)$msg->read32());
        $m = Module_LinkUUp::instance();
        $r = $m->cfgCuddleRange();
        /** @var Position $pa */
        $pa = $m->userSettingValue($a, 'position');
        /** @var Position $pb */
        $pb = $m->userSettingValue($b, 'position');
        if (!$pa || $pb)
        {
            return $this->replyError('err_lup_cuddle_pos');
        }
        if($r > $pa->distanceCalculationPos($pb))
        {
            return $this->replyError('err_lup_cuddle_range');
        }
        if (LUP_Cuddle::getCuddle($a, $b))
        {
            return $this->replyError('err_lup_already_cuddled');
        }
        LUP_Cuddle::cuddle($a, $b);

        $payload = GWS_Message::wrCmd(0x1170);
        $payload .= $msg->wr32((int)$a->getID());
        $payload .= $msg->wr32((int)$m->cfgCuddles($a));
        $payload .= $msg->wr32((int)$b->getID());
        $payload .= $msg->wr32((int)$m->cfgCuddles($b));

        # room
        $ra = LUP_Global::getRoomsForUser($a);
        $rb = LUP_Global::getRoomsForUser($b);
        $rc = array_intersect($ra, $rb);
        $payload .= $rc ? $msg->wr32((int)$rc[0]->getID()) : $msg->wr32(0);

        GWS_Global::send($a, $payload);
        GWS_Global::send($b, $payload);
    }
}

GWS_Commands::register(0x1170, new LUPWS_Cuddle());
