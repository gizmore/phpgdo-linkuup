<?php
namespace GDO\LinkUUp\Test;

use GDO\Address\GDO_Address;
use GDO\File\GDO_File;
use GDO\LinkUUp\LUP_Category;
use GDO\LinkUUp\LUP_Room;
use GDO\Tests\TestCase;
use function PHPUnit\Framework\assertGreaterThanOrEqual;

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

}
