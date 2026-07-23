<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\Method;
use GDO\Gallery\GDO_Gallery;
use GDO\Gallery\Method\Crud;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_CommandForm;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Delete a gallery image.
 *
 * @version 7.0.1
 * @since 6.9.0
 * @author gizmore
 */
class LUPWS_GalleryDelete extends GWS_CommandForm
{

	private GDO_Gallery $gallery;

	public function getMethod() { return Crud::make(); }

	public function fillRequestVars(GWS_Message $msg, Method $method)
	{
		$this->gallery = $this->galleryFor($msg->user());
		$form = $method->getForm();
		$form->getField('id')->var($this->gallery->getID());
		$form->getField('delete_gallery_files')->var([$msg->read32u() => 1]);
	}

	private function galleryFor(GDO_User $user)
	{
		return GDO_Gallery::findBy('gallery_creator', $user->getID());
	}

}

GWS_Commands::register(0x1153, new LUPWS_GalleryDelete());
