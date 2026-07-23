<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Address\GDT_Phone;
use GDO\Core\Application;
use GDO\Core\GDO_Exception;
use GDO\Core\GDO_Module;
use GDO\Core\GDO_RedirectError;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Enum;
use GDO\Core\GDT_String;
use GDO\Core\GDT_UInt;
use GDO\Core\Method;
use GDO\Form\GDT_Form;
use GDO\Gallery\GDO_Gallery;
use GDO\Gallery\Module_Gallery;
use GDO\Net\GDT_Url;
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Divider;
use GDO\UI\GDT_Length;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Page;
use GDO\UI\GDT_Redirect;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;

/**
 * LinkUUp in GDOv7
 *
 * @version 7.0.3
 * @since 6.2.0
 * @author gizmore
 */
final class Module_LinkUUp extends GDO_Module
{

	##############
	### Module ###
	##############
	public int $priority = 150;
	public string $license = 'LinkUUp';

	public function isSiteModule(): bool { return true; }

	public function getDependencies(): array
	{
		return [
            'AboutMe',
			'Account', 'ActivationAlert', 'Address',
			'Admin', 'Avatar',
			'Backup', 'Birthday', 'Bootstrap5Theme',
			'Captcha', 'Classic', 'Comments',
			'Contact', 'CORS', 'Country',
			'CSS', 'Currency',
            'DBMS', 'DSGVO',
			'Facebook', 'Friends', 'Gallery',
			'Instagram',
			'Javascript', 'JPGraph', 'JQueryAutocomplete',
			'Licenses', 'Login',
			'Maps', 'Markdown',
			'News', 'OpenTimes', 'Perf',
			'QRCode', 'Recovery', 'Register',
            'Session', 'Websocket',
		];
	}

	/**
	 * Database entities.
	 */
	public function getClasses(): array
	{
		return [
			LUP_HelpRead::class,
			LUP_Trophy::class,
			LUP_ProfileLike::class,
			LUP_Category::class,
			LUP_Room::class,
			LUP_RoomVisit::class,
			LUP_RoomComments::class,
			LUP_RoomVote::class,
			LUP_Notification::class,
			LUP_SignupGPS::class,
			LUP_QueryMessage::class,
			LUP_RoomWorker::class,
			LUP_RoomWorkerActivation::class,
			LUP_MessageSent::class,
		];
	}

	public function getTheme(): ?string { return 'lup'; }

	public function onLoadLanguage(): void { $this->loadLanguage('lang/linkuup'); }

	##############
	### Config ###
	##############
	public function getConfig(): array
	{
		return [
			GDT_Url::make('lup_app_url')->initial('https://linkuup.de/')->allowAll(),
			GDT_Checkbox::make('lup_guest_query')->initial('0'), # Allow guest querie messages
			GDT_Checkbox::make('lup_open_query')->initial('1'), # No near check for queries
			GDT_Checkbox::make('lup_only_one_chat')->initial('1'), # Auto part all channels before join another room?
			GDT_Checkbox::make('lup_ticket_engine')->initial('0'), # Need to purchase tickets for a room first?
			GDT_Checkbox::make('lup_profile_likes_guests')->initial('0'), # Guests may not like users
            GDT_Length::make('lup_cuddle_range')->initial('0.100'), # Cuddle range
			GDT_UInt::make('lup_num_top_comments')->initial('3')->max(100), # Num Top comments in Room detail.
			GDT_UInt::make('lup_graph_width')->initial('512')->min(32)->max(4096),
			GDT_UInt::make('lup_graph_height')->initial('392')->min(32)->max(4096),
		];
	}

    public function getUserConfig(): array
    {
        return [
            GDT_UInt::make('lup_cuddles')->icon('trophy')->notNull()->initial('0'),
        ];
    }

    public function getACLDefaults(): array
	{
		return [
			'lup_state' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_city' => [GDT_ACLRelation::MEMBERS, '0', null],

			'lup_course_visible' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_icq' => [GDT_ACLRelation::FRIEND_FRIENDS, '0', null],
			'lup_whatsapp' => [GDT_ACLRelation::FRIENDS, '0', null],

			'lup_eyecolor' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_height' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_interest' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_sexo' => [GDT_ACLRelation::MEMBERS, '0', null],

			'lup_has_pet' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_drinks' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_smokes' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_sporty' => [GDT_ACLRelation::MEMBERS, '0', null],
		];
	}

	public function getUserSettings(): array
	{
		return [
			GDT_Divider::make('div_general'),
			GDT_String::make('lup_status'),

			GDT_Divider::make('div_location'),
			GDT_String::make('lup_state'),
			GDT_String::make('lup_city'),

			GDT_Divider::make('div_contact'),
			GDT_ACLRelation::make('lup_course_visible'),

			GDT_Divider::make('div_sexy'),
			GDT_EyeColor::make('lup_eyecolor'),
			GDT_PersonHeight::make('lup_height'),
			GDT_RelationInterest::make('lup_interest'),
			GDT_SexualOrientation::make('lup_sexo'),

			GDT_Divider::make('div_habits'),
			GDT_Enum::make('lup_has_pet')->enumValues('yes', 'no')->emptyLabel('not_specified'),
			GDT_Enum::make('lup_drinks')->enumValues('lup_drink_yes', 'lup_drink_sometimes', 'lup_drink_never')->emptyLabel('not_specified'),
			GDT_Enum::make('lup_smokes')->enumValues('lup_smokes_yes', 'lup_smokes_no_care', 'lup_smokes_no', 'lup_smokes_no_way')->emptyLabel('not_specified'),
			GDT_Enum::make('lup_sporty')->enumValues('lup_sporty', 'lup_unsporty')->emptyLabel('not_specified'),
		];
	}

	public function href_administrate_module(): ?string { return href('LinkUUp', 'Admin'); }

	public function onIncludeScripts(): void
	{
	}

	/**
	 * @throws GDO_Exception
	 */
	public function onInstall(): void
	{
		Install::onInstall($this);
	}

	public function onInitSidebar(): void
	{
		if (GDO_User::current()->isStaff())
		{
			GDT_Page::$INSTANCE->rightBar()->addField(GDT_Link::make('lup_staff')->href(href('LinkUUp', 'Main')));
		}

		GDT_Page::instance()->leftBar()->addField(GDT_Link::make('lup_welcome')->href(href('LinkUUp', 'Welcome')));
	}

	public function cfgAllowGuestQuery(): bool { return $this->getConfigValue('lup_guest_query'); }

	public function cfgOpenQuery(): bool { return $this->getConfigValue('lup_open_query'); }

	public function cfgOnlyOneChat(): bool { return $this->getConfigValue('lup_only_one_chat'); }

    public function cfgCuddleRange(): float { return $this->getConfigValue('lup_cuddle_range'); }

	################
	### Settings ###
	################

	public function cfgTicketEngine(): bool { return $this->getConfigValue('lup_ticket_engine'); }

	public function cfgProfileLikeGuests(): bool { return $this->getConfigValue('lup_profile_likes_guests'); }

	public function cfgNumTopComments(): int { return $this->getConfigValue('lup_num_top_comments'); }

    public function cfgCuddles(GDO_User $user): int { return $this->userSettingValue($user, 'lup_cuddles'); }

	################
	### Override ###
	################

	public function cfgGraphWidth(): int { return $this->getConfigValue('lup_graph_width'); }

	public function cfgGraphHeight(): int { return $this->getConfigValue('lup_graph_height'); }

	/**
	 * @throws GDO_RedirectError
	 */
	public function hookBeforeExecute(Method $method): void
	{
		# Redirect to login if not authenticated
		if (Application::instance()->isWebserver())
		{
			global $me;
			if ( $me && (!GDO_User::current()->isAuthenticated()))
			{

				$allowed = [
					"GDO\\Login\\Method\\Form",
					"GDO\\Avatar\\Method\\Image",
					"GDO\\Avatar\\Method\\ImageUser",
					"GDO\\Captcha\\Method\\Image",
					"GDO\\Country\\Method\\AjaxList",
					"GDO\\Contact\\Method\\Form",
					"GDO\\Core\\Method\\Config",
					"GDO\\Core\\Method\\UserSettings",
					"GDO\\Core\\Method\\GetEnums",
					"GDO\\Core\\Method\\FileNotFound",
					"GDO\\Core\\Method\\Fileserver",
					"GDO\\Core\\Method\\NotAllowed",
					"GDO\\File\\Method\\GetFile",

					"GDO\\Register\\Method\\Form",
					"GDO\\Recovery\\Method\\Form",
					"GDO\\Recovery\\Method\\Change",
					"GDO\\Websocket\\Method\\GetSecret",
					"GDO\\Date\\Method\\Timezone",
					"GDO\\Date\\Method\\TimezoneDetect",
					"GDO\\Account\\Method\\AjaxSettings",
					"GDO\\Core\\Method\\GetTypes",
					"GDO\\Core\\Method\\Impressum",
					"GDO\\Core\\Method\\Privacy",
					"GDO\\DSGVO\\Method\\Accept",
					"GDO\\Language\\Method\\SwitchLanguage",
					"GDO\\Language\\Method\\GetTransData",
					"GDO\\Register\\Method\\Activate",
					"GDO\\Register\\Method\\Guest",
					"GDO\\Sitemap\\Method\\Show",
					"GDO\\LinkUUp\\Method\\CategoryJSON",
					"GDO\\LinkUUp\\Method\\Main",
					"GDO\\LinkUUp\\Method\\Welcome",
					"GDO\\Maps\\Method\\Record",
					"GDO\\Core\\Method\\Error",
				];
				$class = $me->gdoClassName();
				if (!in_array($class, $allowed, true))
				{
					throw new GDO_RedirectError('err_members_only', null, href('Login', 'Form'), GDT_Redirect::CODE);
				}
			}
		}
	}

	##############
	### Helper ###
	##############

	public function shouldShowTopNav()
	{
		global $me;
		$method = $me;
		$withoutMenu = [
			"GDO\\Login\\Method\\Form",
			"GDO\\Recovery\\Method\\Form",
			"GDO\\Login\\Method\\Logout",
		];
		return !in_array($method->gdoClassName(), $withoutMenu, true);
	}

	##############
	### Render ###
	##############
	public function onRenderTabs() { return $this->responsePHP('tabs.php'); }

	public function hookLeftBar(GDT_Bar $navbar)
	{
		$this->templatePHP('leftbar.php', ['navbar' => $navbar]);
	}

	public function hookUserAuthenticated(GDO_User $user)
	{
		GDT_Redirect::to(href('LinkUUp', 'Statistics'));
	}

	#############
	### Hooks ###
	#############

	public function hookUserLoggedOut(GDO_User $user)
	{
		GDT_Redirect::to(href('LinkUUp', 'Statistics'));
	}

	public function hookUserAvtivated(GDO_User $user)
	{
		LUP_RoomWorkerActivation::hookInviteCompleted($user);
		$this->redirectToApp();
	}

	public function hookInviteCompleted(GDO_User $user)
	{
// 		LUP_RoomWorkerActivation::hookInviteCompleted($user);
	}

	private function redirectToApp()
	{
		GDT_Redirect::to($this->cfgAppUrl());
	}

	public function cfgAppUrl() { return $this->getConfigVar('lup_app_url'); }

	public function hookAlreadyActivated()
	{
		$this->redirectToApp();
	}

	public function hookLoginForm(GDT_Form $form)
	{
		$a = $form->actions();
		$a->removeFieldNamed('link_fb_auth');
		$a->removeFieldNamed('link_instagram_auth');
		$a->removeFieldNamed('link_register');
		$a->removeFieldNamed('link_register_guest');
	}

	public function hookRecoveryForm(GDT_Form $form)
	{
		$form->addField(GDT_Link::make('link_login')->href(href('Login', 'Form')));
	}

	public function hookAccountChanged()
	{
		$this->redirectToApp();
	}

	##############
	### Shared ###
	##############

	/**
	 * Get the gallery for a user.
	 */
	public function defaultGallery(GDO_User $user, bool $insert = true): GDO_Gallery
	{
		$gallery = GDO_Gallery::blank([
			'gallery_title' => 'LinkUUp gallery',
			'gallery_creator' => $user->getID(),
			'gallery_acl' => Module_Gallery::instance()->cfgUserACLObject($user)->var,
		]);
		return $insert ? $gallery->insert() : $gallery;
	}

}
