<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\GDO;
use GDO\Form\GDT_Form;
use GDO\Form\MethodCrud;
use GDO\LinkUUp\LUP_Category;

final class CategoryCRUD extends MethodCrud
{

	public function getPermission(): ?string { return 'staff'; }

	public function hrefList(): string
	{
		return href('LinkUUp', 'CategoryList');
	}

	public function gdoTable(): GDO
	{
		return LUP_Category::table();
	}

	public function createFormButtons(GDT_Form $form): void
	{
		if (isset($this->gdo))
		{
			$form->getField('cat_icon')->previewHREF(href('LinkUUp', 'CategoryIcon', '&id=' . $this->gdo->getID() . '&file={id}'));
		}
	}

}
