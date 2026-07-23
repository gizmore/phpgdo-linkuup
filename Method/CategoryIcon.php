<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;

use GDO\Core\Application;
use GDO\Core\GDO_ArgError;
use GDO\Core\GDT;
use GDO\Core\GDT_Object;
use GDO\Core\Method;
use GDO\File\Method\GetFile;
use GDO\LinkUUp\LUP_Category;
use GDO\LinkUUp\Module_LinkUUp;

/**
 * Download an Icon for a category.
 *
 * @version 7.0.3
 */
final class CategoryIcon extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('id')->table(LUP_Category::table())->notNull(),
		];
	}

	public function execute(): GDT
	{
		$category = $this->getCategory();
		if ($file = $category->getFile())
		{
			return GetFile::make()->executeWithId($file->getID());
		}

		return $this->defaultIcon();
	}

	/**
	 * @throws GDO_ArgError
	 */
	public function getCategory(): LUP_Category
	{
		return $this->gdoParameterValue('id');
	}

	private function defaultIcon(): void
	{
		hdr('Content-Type: image/jpeg');
		if (Application::instance()->isUnitTests())
		{
			echo "Sending file category default icon and would die with zero code.\n";
			ob_flush();
			flush();
		}
		else
		{
			die(Module_LinkUUp::instance()->templateFile('img/category/none.jpg'));
		}
	}

}
