<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDT_Float;

/**
 * A person's height in metres.
 *
 * @version 7.0.3
 * @author gizmore
 */
final class GDT_PersonHeight extends GDT_Float
{

	public int|null|float $min = 1.00;
	public int|null|float $max = 2.50;
	public int|float $step = 0.01;

	public function gdtDefaultLabel(): ?string
	{
		return 'person_height';
	}

	public function renderHTML(): string
	{
		$var = $this->getVar();
		return $var === null ? self::none() : t('metres', [$var]);
	}

}
