<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Tuple;
use GDO\Core\Method;
use GDO\LinkUUp\Module_LinkUUp;

final class Main extends Method
{

	public function execute(): GDT
	{
		$tabs = Module_LinkUUp::instance()->onRenderTabs();
		$main = $this->templatePHP('main.php');
		return GDT_Tuple::make()->addFields($tabs, $main);
	}

}
