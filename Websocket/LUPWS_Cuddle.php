<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Websocket;

use GDO\LinkUUp\LUPWS_Command;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Legacy opcode reservation.
 *
 * Cuddles are intentionally redeemed through the signed, short-lived QR URL.
 * The former positional/GPS-only command had no room-bound one-time proof, so
 * it must never be a second way of awarding points.
 */
final class LUPWS_Cuddle extends LUPWS_Command
{
    public function execute(GWS_Message $msg)
    {
        return $this->replyError('err_lup_cuddle_qr');
    }
}

GWS_Commands::register(0x1170, new LUPWS_Cuddle());
