<?php
namespace GDO\LinkUUp\Method;

use GDO\UI\MethodPage;

/**
 * The welcome page for the LUP Backend.
 */
final class Welcome extends MethodPage
{

	public function getMethodTitle(): string
	{
		return t('lup_landing_title');
	}

}
