<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_Object;
use GDO\Core\GDT_Token;
use GDO\Date\GDT_DateTime;
use GDO\Date\GDT_Duration;
use GDO\Payment\GDT_Money;

final class LUP_Ticket extends GDO # implements Orderable
{

	###########
	### GDO ###
	###########
	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('ticket_id'),
			GDT_CreatedBy::make('ticket_creator'),
			GDT_Object::make('ticket_room')->table(LUP_Room::table())->notNull(),
			GDT_DateTime::make('ticket_start')->notNull(),
			GDT_DateTime::make('ticket_end')->notNull(),
			GDT_Duration::make('ticket_duration')->notNull(),
			GDT_Money::make('ticket_price'),
			GDT_Token::make('ticket_token')->notNull(),
		];
	}

// 	public function getRoom() { return $this->queryRoom(); }
// 	public function queryRoom() { return GDO::table('LUP_Room')->queryFirstObject('*', "room_id=".$this->gdoVar('ticket_room_id')); }

// 	public function getToken() { return $this->gdoVar('ticket_token'); }
// 	public function getDuration() { return intval($this->gdoVar('ticket_minutes')) * 60; }
// 	public function getStartDate() { return $this->gdoVar('ticket_start'); }
// 	public function getStartTime() { return Time::getTimestamp($this->getStartDate()); }
// 	public function getEndDate() { return Time::getDate($this->getEndTime()); }
// 	public function getEndTime() { return $this->getStartTime() + $this->getDuration(); }

// 	public static function getLastTicket(LUP_Room $room)
// 	{
// 		$where = sprintf("ticket_room_id=%s", $room->getID());
// 		return self::table(__CLASS__)->selectFirstObject('*', $where, 'ticket_end DESC');
// 	}

// 	public static function getCurrentTicket(LUP_Room $room)
// 	{
// 		$now = Time::getDate(GDO_Date::LEN_MINUTE);
// 		$where = sprintf("ticket_room_id=%s AND ticket_start <= %s and ticket_end >= %s", $room->getID(), $now, $now);
// 		return self::table(__CLASS__)->selectFirstObject('*', $where);
// 	}

// 	public static function generateToken()
// 	{
// 		$chars = Random::HEXUPPER;
// 		return Random::randomKey(4, $chars).'-'.Random::randomKey(4, $chars);
// 	}

// 	public static function overlaps($roomId, $start, $end)
// 	{
// 		return false;
// 		$roomId = (int) $roomId;
// 		$estart = self::escape($start);
// 		$eend = self::escape($end);
// 		$where = "ticket_room_id=$roomId AND ";
// 		$where .= "ticket_start < '$eend' AND ticket_end > '$estart'";
// 		return self::table(__CLASS__)->selectColumn('1', $where) !== false;
// 	}

// 	public function checkSecret($secret)
// 	{
// 		$token = str_replace('-', '', strtolower($this->getToken()));
// 		$secret = str_replace('-', '', strtolower($secret));
// 		return $token === $secret;
// 	}

// 	###############
// 	### Display ###
// 	###############
// 	public function displayStart($iso=null)
// 	{
// 		if (!$iso) {
// 			$iso = Language::getCurrentISO();
// 		}
// 		return Time::displayDateISO($this->getStartDate(), $iso, 'ERROR');
// 	}

// 	public function displayEnd($iso=null)
// 	{
// 		if (!$iso) {
// 			$iso = Language::getCurrentISO();
// 		}
// 		return Time::displayDateISO($this->getEndDate(), $iso, 'ERROR');
// 	}

// 	public function displayDuration($iso=null)
// 	{
// 		if (!$iso) {
// 			$iso = Language::getCurrentISO();
// 		}
// 		return Time::humanDurationISO($iso, $this->getDuration());
// 	}

// 	#################
// 	### Order ###
// 	#################
// 	public function canOrder(GDO_User $user) { return true; }
// 	public function canRefund(GDO_User $user) { return false; }
// 	public function canPayWithGWF(GDO_User $user) { return true; }
// 	public function canAutomizeExec(GDO_User $user) { return true; }
// 	public function needsShipping(GDO_User $user) { return false; }

// 	public function getOrderWidth() { return 0.0; }
// 	public function getOrderHeight() { return 0.0; }
// 	public function getOrderDepth() { return 0.0; }
// 	public function getOrderWeight() { return 0.0; }

// 	public function getOrderModuleName() { return 'LinkUUp'; }
// 	public function getOrderPrice(GDO_User $user) { return floatval($this->gdoVar('ticket_cost')); }
// 	public function getOrderItemName(Module $module, $lang_iso)
// 	{
// 		$room = $this->getRoom();
// 		$name = $room->getName();
// 		$start = $this->displayStart();
// 		$duration = $this->displayDuration();
// 		return $module->langISO($lang_iso, 'order_title', array($duration, $name));
// 	}
// 	public function getOrderDescr(Module $module, $lang_iso)
// 	{
// 		$room = $this->getRoom();
// 		$name = $room->getName();
// 		$start = $this->displayStart();
// 		$duration = $this->displayDuration();
// 		return $module->langISO($lang_iso, 'order_descr', array($duration, $name, $start));
// 	}
// 	public function getOrderStock(GDO_User $user) { return 1; }
// 	public function getOrderCancelURL(GDO_User $user) { return GDO_WEB_ROOT.'index.php?mo=LinkUUp&me=CreateTicket'; }
// 	public function getOrderSuccessURL(GDO_User $user) { return GDO_WEB_ROOT.'index.php?mo=LinkUUp&me=JoinRoom'; }

// 	public function displayOrder(Module $module)
// 	{
// 		$tVars = array(
// 			'ticket' => $this,
// 		);
// 		return $module->templatePHP('order.php', $tVars);
// 	}

// 	public function executeOrder(Module $module, GDO_User $user, &$message)
// 	{
// 		$room = $this->getRoom();
// #		$token = $this->gdoVar('your_token');
// 		if (false === (LUP_Ticket::insertTicket($module, $this, $user, $room)))
// 		{
// 			$message = GDO_HTML::err('ERR_DATABASE', array(__FILE__, __LINE__));
// 			return false;
// 		}
// 		$name = $room->getName();
// 		$start = $this->displayStart();
// 		$duration = $this->displayDuration();
// 		$message = $module->message('msg_purchased', array($duration, $name, $start));
// 		return true;
// 	}
}
