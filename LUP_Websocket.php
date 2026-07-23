<?php
namespace GDO\LinkUUp;

use Exception;
use GDO\Core\Debug;
use GDO\DB\Database;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;

// include 'LUP_Global.php';

final class LUP_Websocket extends GWS_Commands
{

	public function init() {}

	public function connect(GDO_User $user)
	{
// 		$this->deliverQueryMessages($user);
// 		$this->deliverQueryReadMessages($user);
	}

	public function disconnect(GDO_User $user) { LUP_Global::userQuit($user); }

	public function logout(GDO_User $user) { LUP_Global::userQuit($user); }

	public function timer()
	{
		try
		{
			# Keep mysql connection alive
			Database::instance()->queryRead('SELECT 1 FROM DUAL');
		}
		catch (Exception $e)
		{
			Debug::exception_handler($e);
			# Ignore failures
		}
	}

// 	private function deliverQueryMessages(GDO_User $user)
// 	{
// 		$table = LUP_QueryMessage::table();
// 		$query = $table->select()->where("lupqm_delivered IS NULL AND lupqm_to=" . $user->getID());
// 		$result = $query->exec();
// 		while ($qmsg = $table->fetch($result))
// 		{
// 			if ($qmsg->deliver())
// 			{
// 				$payload = GWS_Message::payload(0x1109) . $qmsg->payloadStatus();
// 				GWS_Global::sendBinary($qmsg->getFromUser(), $payload);
// 			}
// 		}
// 	}

// 	private function deliverQueryReadMessages(GDO_User $user)
// 	{
// 		$table = LUP_QueryMessage::table();
// 		$query = $table->select()->where("lupqm_read IS NOT NULL AND lupqm_ack=0 AND lupqm_from=" . $user->getID());
// 		$result = $query->exec();
// 		while ($qmsg = $table->fetch($result))
// 		{
// 			$payload = GWS_Message::payload(0x1109) . $qmsg->payloadStatus();
// 			if (GWS_Global::sendBinary($user, $payload))
// 			{
// 				$qmsg->acknowledge();
// 			}
// 		}
// 	}
}
