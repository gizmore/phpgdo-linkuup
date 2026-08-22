<?php
namespace GDO\LinkUUp\Websocket;

use GDO\File\GDT_ImageFiles;
use GDO\File\GDO_File;
use GDO\Gallery\GDO_Gallery;
use GDO\LinkUUp\LUPWS_Command;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/** Attach the completed Flow upload to the current user's LinkUUp gallery. */
final class LUPWS_GalleryUpload extends LUPWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$gallery = $this->galleryFor($msg->user());
		// Consume the legacy form payload and the exact Flow identifier sent by
		// the browser. The identifier lets us find the right upload even though
		// HTTP Flow and websocket use separate PHP sessions.
		$msg->read32u();
		$msg->readString();
		$msg->readString();
		$msg->read16u();
		$identifier = preg_replace('/[^A-Za-z0-9_-]/', '', $msg->readString());
		$files = $identifier ? $this->fileForIdentifier($identifier) : [];
		if (!$files)
		{
			$files = GDT_ImageFiles::make('gallery_files')->getFiles();
		}
		if (!$files)
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_upload_failed'));
		}
		// Flow keeps markers for earlier completed uploads in its temporary
		// directory. Attach only the newest file from this upload, otherwise a
		// previously selected photo is added again with every new upload.
		usort($files, static fn($a, $b) => $b->getID() <=> $a->getID());
		$gallery->addFiles([$files[0]]);
		return $msg->replyBinary($msg->cmd(), '');
	}

	private function fileForIdentifier(string $identifier): array
	{
		foreach (glob(GDO_TEMP_PATH . 'flow/*/gallery_files/' . $identifier, GLOB_ONLYDIR) ?: [] as $dir)
		{
			if (is_file($dir . '/id') && ($id = trim((string)file_get_contents($dir . '/id'))) && ($file = GDO_File::getById($id)) && is_file($file->getPath()))
			{
				return [$file];
			}
			if (is_file($dir . '/0') && is_file($dir . '/name'))
			{
				$file = GDO_File::fromPath(trim((string)file_get_contents($dir . '/name')), $dir . '/0');
				$file->insert();
				file_put_contents($dir . '/id', $file->getID());
				return [$file];
			}
		}
		return [];
	}

	private function galleryFor(GDO_User $user): GDO_Gallery
	{
		if ($gallery = GDO_Gallery::table()->select()->
			where('gallery_creator=' . $user->getID())->
			order('(SELECT COUNT(*) FROM gdo_galleryimage WHERE files_object=gallery_id) DESC, gallery_id ASC')->
			first()->exec()->fetchObject())
		{
			return $gallery;
		}
		return Module_LinkUUp::instance()->defaultGallery($user);
	}
}

GWS_Commands::register(0x1152, new LUPWS_GalleryUpload());
