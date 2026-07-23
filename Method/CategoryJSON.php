<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Array;
use GDO\Core\MethodAjax;
use GDO\LinkUUp\LUP_Category;

/**
 * Get all LUP categories.
 *
 * @author gizmore
 */
final class CategoryJSON extends MethodAjax
{

	public function getMethodDescription(): string
	{
		return 'All categories on LinkUUp';
	}

	public function execute(): GDT
	{
		$table = LUP_Category::table();
		$categories = $table->select()->exec()->
		fetchAllArray2dObject();
		$categories = array_map(function (LUP_Category $cat)
		{
			return $cat->renderJSON();
		}, $categories);

		return GDT_Array::make()->value($categories);
	}

}
