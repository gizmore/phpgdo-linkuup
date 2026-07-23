<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\GDT_Response;
use GDO\Core\Method;
use GDO\LinkUUp\LUP_Global;
use GDO\Votes\Method\Up;
use GDO\Websocket\Server\GWS_CommandMethod;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Vote on a room.
 *
 * @version 7.0.1
 * @since 6.9.0
 * @author gizmore
 */
class LUPWS_RoomVote extends GWS_CommandMethod
{

	private $room;

	public function getMethod() { return Up::make(); }

	public function execute(GWS_Message $msg)
	{
		if (!($this->room = LUP_Global::getRoom($msg->read32())))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_room'));
		}
        $msg->move(-4); // Reset cursor
		if ($this->room->isDisabled())
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_room_disabled'));
		}
		return parent::execute($msg);
	}

	public function fillRequestVars(GWS_Message $msg, Method $method)
	{
		$method->gdoParameter('gdo', false)->var('GDO\\LinkUUp\\LUP_RoomVote');
		$method->gdoParameter('id', false)->var($this->room->getID());
		$method->gdoParameter('rate', false)->var($msg->read8());
	}

	public function replySuccess(GWS_Message $msg, GDT_Response $response)
	{
		$payload = $this->gdoToBinary($this->room) .
			$this->gdoToBinary($this->room->getAddressOrBlank()) .
			$msg->wr32(0);
		$msg->replyBinary($msg->cmd(), $payload);
	}

}

GWS_Commands::register(0x1120, new LUPWS_RoomVote());
