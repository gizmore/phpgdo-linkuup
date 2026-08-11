<?php
namespace GDO\LinkUUp\Websocket;

use GDO\File\GDT_ImageFiles;
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
		$files = GDT_ImageFiles::make('gallery_files')->getFiles();
		if (!$files)
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_upload_failed'));
		}
		$gallery->addFiles($files);
		return $msg->replyBinary($msg->cmd(), '');
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
