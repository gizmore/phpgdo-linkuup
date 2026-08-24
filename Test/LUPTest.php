<?php
namespace GDO\LinkUUp\Test;

use GDO\Address\GDO_Address;
use GDO\File\GDO_File;
use GDO\LinkUUp\LUP_Category;
use GDO\LinkUUp\LUP_Cuddle;
use GDO\LinkUUp\LUP_CuddleToken;
use GDO\LinkUUp\LUP_Room;
use GDO\LinkUUp\LUP_SignupGPS;
use GDO\LinkUUp\LUP_QueryThread;
use GDO\LinkUUp\Method\Cuddle;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Tests\GDT_MethodTest;
use GDO\Tests\TestCase;
use GDO\User\GDO_User;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertTrue;

/**
 * LinkUUp automated code quality test cases.
 *
 * @since 7.0.3
 * @author gizmore
 */
final class LUPTest extends TestCase
{

	private LUP_Category $germany;
	private GDO_File $germanyIcon;
	private GDO_Address $germanyAddress;

	public function testLinkUUp()
	{
		assertGreaterThanOrEqual(1, LUP_Category::table()->countWhere());
		assertGreaterThanOrEqual(1, GDO_Address::table()->countWhere());
		assertGreaterThanOrEqual(1, LUP_Room::table()->countWhere());
	}

	public function testQueryThreadsSplitAfterAnHour(): void
	{
		$gizmore = GDO_User::getByName('gizmore');
		$peter = GDO_User::getByName('Peter');
		$thread = LUP_QueryThread::forMessage($gizmore, $peter);
		assertSame($thread->getID(), LUP_QueryThread::forMessage($peter, $gizmore)->getID());
		$thread->saveVar('lupqt_updated', \GDO\Date\Time::getDate(time() - 3601));
		$next = LUP_QueryThread::forMessage($gizmore, $peter);
		assertFalse($thread->getID() === $next->getID());
	}

	/** A QR token may be redeemed once, and rewards both participating users. */
	public function testCuddleTokenRedemption(): void
	{
		$issuer = GDO_User::getByName('gizmore');
		$scanner = GDO_User::getByName('Peter');
		assertTrue($issuer instanceof GDO_User);
		assertTrue($scanner instanceof GDO_User);

		$module = Module_LinkUUp::instance();
		$issuerBefore = $module->cfgCuddles($issuer);
		$scannerBefore = $module->cfgCuddles($scanner);
		$issuerGPSBefore = LUP_SignupGPS::getById($issuer->getID());
		$scannerGPSBefore = LUP_SignupGPS::getById($scanner->getID());
		$token = null;

		try
		{
			LUP_SignupGPS::updateGPS($issuer, 52.3237406, 10.2440026);
			LUP_SignupGPS::updateGPS($scanner, 52.3238000, 10.2440026);
			$this->user($issuer);
			$token = LUP_CuddleToken::issue($issuer, $module->cfgCuddleTokenTTL());
			$mac = $token->signature();
			assertFalse($token->isExpired());
			assertFalse($token->isUsed());
			assertFalse($issuer->getID() === $scanner->getID());
			assertFalse(LUP_Cuddle::exists($issuer, $scanner));
			assertSame($issuer->getID(), $token->issuer()->getID());
			assertTrue(hash_equals($token->signature(), $mac));
			$stored = LUP_CuddleToken::findToken($token->gdoVar('ctoken_token'));
			assertSame($mac, $stored?->signature(), 'Stored Cuddle token must keep its MAC.');
			$parameters = Cuddle::make()->gdoParameterCache();
			assertTrue($parameters['token']->validate($token->gdoVar('ctoken_token')), $parameters['token']->renderError());
			assertTrue($parameters['mac']->validate($mac), $parameters['mac']->renderError());

			$this->user($scanner);
			$call = GDT_MethodTest::make()->runAs($scanner)->method(Cuddle::make())->inputs([
				'token' => $token->gdoVar('ctoken_token'),
				'mac' => $mac,
			]);
			$result = $call->execute();
			assertFalse($result->hasError());
			assertTrue(LUP_CuddleToken::findToken($token->gdoVar('ctoken_token'))->isUsed());
			assertSame($issuerBefore + 1, $module->cfgCuddles($issuer));
			assertSame($scannerBefore + 1, $module->cfgCuddles($scanner));
			LUP_SignupGPS::updateGPS($scanner, 52.3337406, 10.2440026);
			$tooFarToken = LUP_CuddleToken::issue($issuer, $module->cfgCuddleTokenTTL());
			$tooFar = GDT_MethodTest::make()->runAs($scanner)->method(Cuddle::make())->inputs([
				'token' => $tooFarToken->gdoVar('ctoken_token'),
				'mac' => $tooFarToken->signature(),
			])->execute();
			assertTrue($tooFar->hasError());
			$tooFarToken->delete();

			$again = GDT_MethodTest::make()->method(Cuddle::make())->inputs([
				'token' => $token->gdoVar('ctoken_token'),
				'mac' => $mac,
			])->execute();
			assertTrue($again->hasError());
		}
		finally
		{
			$key = LUP_Cuddle::key($issuer, $scanner, LUP_Cuddle::utcDay());
			if ($cuddle = LUP_Cuddle::getByVars(['cuddle_key' => $key]))
			{
				$cuddle->delete();
			}
			if ($token)
			{
				$token->delete();
			}
			$module->saveUserSetting($issuer, 'lup_cuddles', (string)$issuerBefore);
			$module->saveUserSetting($scanner, 'lup_cuddles', (string)$scannerBefore);
			foreach ([[$issuer, $issuerGPSBefore], [$scanner, $scannerGPSBefore]] as [$user, $position])
			{
				if ($position)
				{
					$point = $position->gdoValue('lsp_pos');
					LUP_SignupGPS::updateGPS($user, $point->getLat(), $point->getLng());
				}
				elseif ($position = LUP_SignupGPS::getById($user->getID()))
				{
					$position->delete();
				}
			}
		}
	}

}
