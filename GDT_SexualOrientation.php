<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDT_Enum;

class GDT_SexualOrientation extends GDT_Enum
{

	public function gdtDefaultLabel(): ?string
	{
		return 'lup_sexual_orientation';
	}

	protected function __construct()
	{
		parent::__construct();
		$this->enumValues('men', 'women', 'both');
		$this->emptyLabel('not_specified');
	}

}
