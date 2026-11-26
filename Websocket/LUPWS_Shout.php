<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Websocket;

use GDO\DB\Database;
use GDO\LinkUUp\LUP_Global;
use GDO\LinkUUp\LUPWS_Command;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\User\GDO_UserSetting;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/** Send one paid message to every currently occupied Location. */
final class LUPWS_Shout extends LUPWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$user = $msg->user();
		if ($user->isGuest())
		{
			return $msg->rplyError('err_member_only');
		}
		$radius = $msg->read32u();
		$text = trim($msg->readString());
		$lat = $lng = null;
		if ($msg->hasMore(8))
		{
			$lat = $msg->readFloat();
			$lng = $msg->readFloat();
			if (!is_finite($lat) || !is_finite($lng) || abs($lat) > 90 || abs($lng) > 180)
			{
				return $msg->rplyError('err_lup_shout_position');
			}
		}
		if ($msg->hasMore(4))
		{
			$radius = $msg->read32u() / 1000;
		}
		if ($text === '' || strlen($text) > 512)
		{
			return $msg->rplyError('err_lup_shout_text');
		}
		if ($radius < 0.001)
		{
			return $msg->rplyError('err_lup_shout_radius');
		}
		$gps = LUP_Global::lastPositionFor($user);
		if (!$gps)
		{
			return $msg->rplyError('err_lup_shout_position');
		}

		$cost = (int)ceil($radius * Module_LinkUUp::instance()->cfgShoutCostPerKM());
		if ($lat !== null && $msg->hasMore(4))
		{
			// A custom centre requires explicit agreement to this shout's current total.
			$cost += Module_LinkUUp::instance()->cfgShoutCenterFee();
			if ($msg->read32u() !== $cost)
			{
				return $msg->rplyError('err_lup_shout_refresh');
			}
		}
		elseif ($lat !== null)
		{
			// Older clients sent float32 coordinates even for GPS shouts.
			if (abs($lat - $gps[0]) > 0.00001 || abs($lng - $gps[1]) > 0.00001)
			{
				return $msg->rplyError('err_lup_shout_refresh');
			}
			$lat = $lng = null;
		}
		if (!$this->chargeCredits($user, $cost))
		{
			return $msg->rplyError('err_lup_shout_credits', [$cost, $this->creditBalance($user)]);
		}

		[$locations, $recipients] = LUP_Global::shout($user, $text, $radius, $lat, $lng);
		return $msg->replyBinary($msg->cmd(),
			GWS_Message::wr32($this->creditBalance($user)) .
			GWS_Message::wr32((int)round($radius * 1000)) .
			GWS_Message::wr32($locations) .
			GWS_Message::wr32($recipients));
	}

	/** Conditional update makes concurrent shouts unable to overspend credits. */
	private function chargeCredits(GDO_User $user, int $cost): bool
	{
		if ($cost <= 0)
		{
			return true;
		}
		$table = GDO_UserSetting::table()->gdoTableIdentifier();
		$userId = (int)$user->getID();
		Database::instance()->queryWrite("UPDATE {$table} SET uset_var=uset_var-{$cost} WHERE uset_user={$userId} AND uset_name='credits' AND uset_var+0>={$cost}");
		return Database::instance()->affectedRows() === 1;
	}

	private function creditBalance(GDO_User $user): int
	{
		$setting = GDO_UserSetting::getById($user->getID(), 'credits');
		return $setting ? (int)$setting->gdoVar('uset_var') : 0;
	}
}

GWS_Commands::register(0x1166, new LUPWS_Shout());
