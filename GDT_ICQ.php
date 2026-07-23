<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDT_String;

class GDT_ICQ extends GDT_String
{

	protected function __construct()
	{
		parent::__construct();
		$this->max(9);
		$this->ascii();
		$this->caseS();
		$this->pattern("/[0-9]{4,9}/");
	}

}
