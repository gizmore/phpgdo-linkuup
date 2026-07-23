<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\Method;

final class Admin extends Method
{

	public function getPermission(): ?string { return 'staff'; }

	public function execute(): GDT
	{
		return $this->templatePHP('page/admin.php');
	}

}
