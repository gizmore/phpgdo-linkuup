<?php
namespace GDO\LinkUUp;

use GDO\Core\GDT_Enum;

/**
 * Eye-Color.
 *
 * @version 7.0.1
 * @author gizmore
 */
final class GDT_EyeColor extends GDT_Enum
{

	public static array $COLORS = [
		'amber',
		'green',
		'green_brown',
		'gray',
		'blue',
		'light_brown',
		'light_blue',
		'blue_green',
	];

	protected function __construct()
	{
		parent::__construct();
        $this->icon('eye');
		$this->label('eye_color');
		$this->enumValues(...self::$COLORS);
		$this->emptyLabel('not_specified');
// 		$this->emptyInitial('not_specified');
	}

}
