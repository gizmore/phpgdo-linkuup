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
		if ($gallery = GDO_Gallery::table()->select()->
			where('gallery_creator=' . $user->getID())->
			// Failed older uploads left empty gallery records behind. Prefer the
			// gallery that actually contains the user's photos, then use the
			// oldest empty gallery as the stable destination for a first upload.
			order('(SELECT COUNT(*) FROM gdo_galleryimage WHERE files_object=gallery_id) DESC, gallery_id ASC')->
			first()->exec()->fetchObject())
		{
			return $gallery;
		}
		// A new user also needs a persisted gallery id before Flow can upload.
		return Module_LinkUUp::instance()->defaultGallery($user);
	}

}

GWS_Commands::register(0x1151, new LUPWS_Gallery());
