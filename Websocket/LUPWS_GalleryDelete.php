<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Gallery\GDO_Gallery;
use GDO\Gallery\GDO_GalleryImage;
use GDO\LinkUUp\LUPWS_Command;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/** Delete one of the authenticated user's gallery images. */
final class LUPWS_GalleryDelete extends LUPWS_Command
{
	public function execute(GWS_Message $msg)
	{
		$fileId = $msg->read32u();
		$gallery = $this->galleryFor($msg->user());
		$image = GDO_GalleryImage::table()->select()->
			where("files_object={$gallery->getID()} AND files_file={$fileId}")->
			first()->exec()->fetchObject();
		if (!$image || !($file = $image->getFile()))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_file_not_found'));
		}
		$file->delete();
		return $msg->replyBinary($msg->cmd(), '');
	}

	private function galleryFor(GDO_User $user): GDO_Gallery
	{
		return GDO_Gallery::table()->select()->
			where('gallery_creator=' . $user->getID())->
			order('(SELECT COUNT(*) FROM gdo_galleryimage WHERE files_object=gallery_id) DESC, gallery_id ASC')->
			first()->exec()->fetchObject();
	}
}

GWS_Commands::register(0x1153, new LUPWS_GalleryDelete());
