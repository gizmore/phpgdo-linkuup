<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Core\Method;
use GDO\Form\MethodForm;
use GDO\Gallery\GDO_Gallery;
use GDO\Gallery\Method\Crud;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_CommandForm;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

class LUPWS_GalleryUpload extends GWS_CommandForm
{

	public function getMethod() { return Crud::make(); }

    /**
     * @param MethodForm $method
     */
	public function fillRequestVars(GWS_Message $msg, Method $method)
	{
		$this->gallery = $this->galleryFor($msg->user());
        $method->addInput('create', '');
        $method->addInput('edit', '1');
		$method->gdoParameter('id')->var($this->gallery->getID());
	}

	private function galleryFor(GDO_User $user)
	{
		if (!($gallery = GDO_Gallery::table()->getBy('gallery_creator', $user->getID())))
		{
			$gallery = Module_LinkUUp::instance()->defaultGallery($user);
		}
		return $gallery;
	}

	public function afterReplySuccess(GWS_Message $msg) {}

}

GWS_Commands::register(0x1152, new LUPWS_GalleryUpload());
