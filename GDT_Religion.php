<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDT_Enum;

/** A person's optional religion or worldview. */
final class GDT_Religion extends GDT_Enum
{

	public const VALUES = [
		'religion_christian',
		'religion_muslim',
		'religion_jewish',
		'religion_egyptian',
		'religion_hindi',
		'religion_romanian',
		'religion_vikings',
		'religion_buddhism',
		'religion_atheist',
		'religion_other',
	];

	protected function __construct()
	{
		parent::__construct();
		$this->icon('account_balance');
		$this->label('lup_religion');
		$this->enumValues(...self::VALUES);
		$this->emptyLabel('not_specified');
	}

}
