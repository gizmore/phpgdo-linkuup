<?php
namespace GDO\LinkUUp\Method;

use GDO\Core\
{GDO_ArgError, GDT, GDT_Object, GDT_String, Method};
use GDO\File\GDO_File;
use GDO\File\Method\GetFile;
use GDO\LinkUUp\
{LUP_Room, Module_LinkUUp};
use GDO\Util\Common;

final class RoomImage extends Method
{

	public function gdoParameters(): array
	{
		return [
			GDT_Object::make('id')->notNull()->table(LUP_Room::table()),
			GDT_String::make('variant'),
		];

	}

	/**
	 * @throws GDO_ArgError
	 */
	public function getRoom(): LUP_Room
	{
		return $this->gdoParameterValue('id');
	}

	public function execute(): GDT
	{
		$room = $this->getRoom();
		if ($variant = $this->gdoParameterVar('variant'))
		{
			# security
			$variant = preg_replace('/[^a-z]/', '', $variant);
		}
		if ((!$room) || (!($image = $room->getImage())))
		{
			hdr('Content-Type: image/jpeg');
			if ($variant)
			{
				$variant = "_{$variant}";
			}
			return GDT_String::make()->var(Module_LinkUUp::instance()->templateFile("img/default_room_image{$variant}.jpg"));
		}
		return $this->renderImage($image, GetFile::make(), (string) $variant);
	}

	private function renderImage(GDO_File $file, GetFile $method, string $variant): GDT
	{
		return $method->executeWithId($file->getID(), $variant);
	}

}
