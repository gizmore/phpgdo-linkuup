<?php
namespace GDO\LinkUUp;

use GDO\Websocket\Server\GWS_Command;

abstract class LUPWS_Command extends GWS_Command
{

	##############
	### Config ###
	##############
	public function replyError($key, $args = [])
	{
		return $this->replyErrorCode($this->message->cmd(), $key, $args);
	}

	public function replyErrorCode($code, $key, $args = [])
	{
		return $this->message->replyErrorMessage($code, $this->t($key, $args));
	}

	#################
	### Messaging ###
	#################

	public function t($key, $args = [])
	{
		return tusr($this->user(), $key, $args);
	}

	protected function cfgOnlyOneChat()
	{
		return Module_LinkUUp::instance()->cfgOnlyOneChat();
	}

	protected function cfgTicketEngine()
	{
		return Module_LinkUUp::instance()->cfgTicketEngine();
	}

}
