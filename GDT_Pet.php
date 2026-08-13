<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDT_Enum;

/** A person's optional pet type. */
final class GDT_Pet extends GDT_Enum
{
	public const VALUES = [
		'pet_cat',
		'pet_dog',
		'pet_fish',
		'pet_reptile',
		'pet_bird',
		'pet_other_mammal',
		'pet_other',
		'pet_none',
		'pet_wished',
	];

	protected function __construct()
	{
		parent::__construct();
		$this->icon('pets');
		$this->label('lup_has_pet');
		$this->enumValues(...self::VALUES);
		$this->emptyLabel('PLEASE_SELECT');
	}
}
