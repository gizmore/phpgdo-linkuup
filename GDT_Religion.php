<?php
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
		'religion_chappyology',
		'religion_other',
	];

	protected function __construct()
	{
		parent::__construct();
		$this->icon('cross');
		$this->label('lup_religion');
		$this->enumValues(...$this->valuesSortedForLanguage());
		$this->emptyLabel('not_specified');
	}

	/** Sort labels for the active language while persisting the stable enum keys. */
	private function valuesSortedForLanguage(): array
	{
		$values = self::VALUES;
		usort($values, static function(string $a, string $b): int {
			return strnatcasecmp(t('enum_' . $a), t('enum_' . $b));
		});
		return $values;
	}

}
