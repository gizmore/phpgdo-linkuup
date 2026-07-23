<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Name;
use GDO\Core\GDT_Template;
use GDO\File\GDO_File;
use GDO\File\GDT_ImageFile;
use GDO\UI\GDT_Color;

/**
 * A linkuup room category.
 * @version 7.0.3
 */
final class LUP_Category extends GDO
{

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('cat_id'),
			GDT_Name::make('cat_name')->utf8()->notNull(),
			GDT_Color::make('cat_color'),
			GDT_ImageFile::make('cat_icon'),
		];
	}

	public function getFile(): ?GDO_File { return $this->gdoValue('cat_icon'); }

	public function href_edit(): string { return href('LinkUUp', 'CategoryCRUD', "&id={$this->getID()}"); }

	public function getName(): ?string { return $this->gdoVar('cat_name'); }


	public function renderHTML(): string { return GDT_Template::php('LinkUUp', 'cell/category.php', ['gdo' => $this]); }

	public function renderOption(): string { return (string) $this->getName(); }


}
