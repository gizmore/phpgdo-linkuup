<?php
namespace GDO\LinkUUp\Websocket;

use GDO\Gallery\GDO_Gallery;
use GDO\LinkUUp\LUPWS_Command;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;
use GDO\Websocket\Server\GWS_Commands;
use GDO\Websocket\Server\GWS_Message;

/**
 * Get users gallery and images.
 *
 * @author gizmore
 */
class LUPWS_Gallery extends LUPWS_Command
{

	public function execute(GWS_Message $msg)
	{
		# Get user
		$userid = $msg->read32u();
		$user = GDO_User::findById($userid);

		# Get user's gallery
		$gallery = $this->galleryForUser($user);

		$reason = '';
		if (!$gallery->canView($msg->user(), $reason))
		{
			return $msg->replyErrorMessage($msg->cmd(), t('err_gallery_view_permission', [$reason]));
		}

		# Payload is gallery
		$payload = $this->gdoToBinary($gallery);

		# Plus all images
		if ($gallery->isPersisted())
		{
			foreach ($gallery->getImages() as $image)
			{
				$payload .= $this->gdoToBinary($image);
			}
		}

		# Send payload
		$msg->replyBinary($msg->cmd(), $payload);
	}

	##############
	### Helper ###
	##############
	protected function galleryForUser(GDO_User $user)
	{
		if ($gallery = GDO_Gallery::table()->getBy('gallery_creator', $user->getID()))
		{
			return $gallery;
		}
		return Module_LinkUUp::instance()->defaultGallery($user, false);
	}

}

GWS_Commands::register(0x1151, new LUPWS_Gallery());
