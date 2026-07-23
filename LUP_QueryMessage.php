<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_String;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;
use GDO\User\GDO_User;
use GDO\User\GDT_User;
use GDO\Websocket\Server\GWS_Global;
use GDO\Websocket\Server\GWS_Message;

final class LUP_QueryMessage extends GDO
{

	###########
	### GDO ###
	###########
	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('lupqm_id'),
			GDT_User::make('lupqm_from')->notNull(),
			GDT_User::make('lupqm_to')->notNull(),
			GDT_String::make('lupqm_text')->max(768)->notNull(),
			GDT_CreatedAt::make('lupqm_created')->notNull(),
			GDT_DateTime::make('lupqm_delivered'),
			GDT_DateTime::make('lupqm_read'),
			GDT_Checkbox::make('lupqm_ack')->notNull()->initial('0'),
			GDT_Checkbox::make('lupqm_from_deleted')->notNull()->initial('0'),
			GDT_Checkbox::make('lupqm_to_deleted')->notNull()->initial('0'),
		];
	}

	public function isTo(GDO_User $user) { return $this->getToID() === $user->getID(); }

	public function getToID() { return $this->gdoVar('lupqm_to'); }

	public function getID(): ?string { return $this->gdoVar('lupqm_id'); }

	public function isFrom(GDO_User $user) { return $this->getFromID() === $user->getID(); }

	public function getFromID() { return $this->gdoVar('lupqm_from'); }

	public function isRead() { return $this->gdoVar('lupqm_read') !== null; }

	public function isDelivered() { return $this->gdoVar('lupqm_read') !== null; }

	public function isAcknowledged() { return $this->gdoVar('lupqm_ack') !== '0'; }

	public function deliver()
	{
		// Patched payload
		$now = Time::getDate();
		$this->setVar('lupqm_delivered', $now);
		$payload = GWS_Message::payload(0x1108) . $this->payload();
		$this->setVar('lupqm_delivered', null);

		// Success delivery?
		if (GWS_Global::sendBinary($this->getToUser(), $payload))
		{
			return $this->saveVar('lupqm_delivered', $now);
		}
		return false;
	}

	public function payload()
	{
		return
			GWS_Message::wr32($this->getID()) .
			GWS_Message::wr32($this->getFromID()) .
			GWS_Message::wr32($this->getToID()) .
			GWS_Message::wrS($this->getText()) .
			GWS_Message::wr32($this->gdoValue('lupqm_created')) .
			GWS_Message::wr32($this->gdoValue('lupqm_delivered')) .
			GWS_Message::wr32($this->gdoValue('lupqm_read')) .
			GWS_Message::wr8($this->gdoVar('lupqm_ack')) .
			GWS_Message::wr8($this->gdoVar('lupqm_from_deleted')) .
			GWS_Message::wr8($this->gdoVar('lupqm_to_deleted'));
	}

	public function getText() { return $this->gdoVar('lupqm_text'); }

	###############
	### Payload ###
	###############

	public function getToUser() { return $this->gdoValue('lupqm_to'); }

	public function markRead()
	{
		$now = Time::getDate();
		$this->saveVar('lupqm_read', $now);
		$payload = GWS_Message::payload(0x1109) . $this->payloadStatus();
		return GWS_Global::sendBinary($this->getFromUser(), $payload);
	}

	###############
	### Deliver ###
	###############

	/**
	 * 13 byte payload for delivery status.
	 *
	 * @return string
	 */
	public function payloadStatus()
	{
		return
			GWS_Message::wr32($this->getID()) .
			GWS_Message::wr32($this->gdoValue('lupqm_delivered')) .
			GWS_Message::wr32($this->gdoValue('lupqm_read')) .
			GWS_Message::wr8($this->gdoVar('lupqm_ack'));
	}

	public function getFromUser() { return $this->gdoValue('lupqm_from'); }

	public function acknowledge()
	{
		return $this->saveVar('lupqm_ack', '1');
	}

}
