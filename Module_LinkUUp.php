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
use GDO\Core\GDT_Secret;
use GDO\Core\GDT_String;
use GDO\Core\GDT_UInt;
use GDO\Date\GDT_Duration;
use GDO\Core\Method;
use GDO\Form\GDT_Form;
use GDO\Gallery\GDO_Gallery;
use GDO\Gallery\Module_Gallery;
use GDO\LinkUUp\Method\Welcome;
use GDO\LinkUUp\install\Install;
use GDO\Maps\GDT_Velocity;
use GDO\Net\GDT_Url;
use GDO\PaymentCredits\GDT_Credits;
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
			'Captcha', 'Classic', 'CLI', 'Comments',
			'Contact', 'CORS', 'Country',
			'CSS', 'Currency',
            'DBMS', 'DSGVO',
			'Favicon', 'FontAwesome', 'Friends', 'Gallery', 'GoogleAuth',
			'Javascript', 'JPGraph', 'JQueryAutocomplete',
			'Login', 'Logs',
			'Maps',
			'News', 'OpenTimes', 'Perf',
			'PaymentBank', 'PaymentCredits', 'PaymentPaypal',
			'QRCode', 'Recovery', 'Register',
			'Session', 'Sitemap', 'Websocket',
		];
	}

	/**
	 * Database entities.
	 */
	public function getClasses(): array
	{
		return [
			LUP_Cuddle::class,
			LUP_CuddleToken::class,
			LUP_HelpRead::class,
			LUP_Trophy::class,
			LUP_ProfileLike::class,
			LUP_Category::class,
			LUP_Room::class,
            LUP_OperatorApplication::class,
			LUP_RoomVisit::class,
			LUP_RoomComments::class,
			LUP_RoomVote::class,
			LUP_Notification::class,
			LUP_SignupGPS::class,
			LUP_QueryThread::class,
			LUP_QueryMessage::class,
			LUP_Workers::class,
			LUP_RoomWorkerActivation::class,
			LUP_MessageSent::class,
		];
	}

	public function getTheme(): ?string { return 'lup'; }

	public function defaultMethod(): Method { return Welcome::make(); }

	public function onLoadLanguage(): void { $this->loadLanguage('lang/linkuup'); }

	##############
	### Config ###
	##############
	public function getConfig(): array
	{
		return [
			GDT_Url::make('lup_app_url')->initial('https://app.www.linkuup.de/')->allowAll(),
			GDT_Checkbox::make('lup_guest_query')->initial('0'), # Allow guest querie messages
			GDT_Checkbox::make('lup_open_query')->initial('1'), # No near check for queries
			GDT_Checkbox::make('lup_only_one_chat')->initial('0'), # Auto part all channels before join another room?
			GDT_UInt::make('lup_max_locations')->initial('10')->min(1)->max(1000), # Maximum locations sent to the app per catalogue request
			GDT_UInt::make('lup_msg_bufsize')->initial('3')->max(100), # Volatile messages replayed when joining a room
			GDT_Secret::make('lup_connector_secret')->initial(require __DIR__ . '/secret_gdo.php'), # Authenticates the PyGDO LUP connector callback
			GDT_UInt::make('lup_dog_backlog')->initial('20')->max(100), # Volatile room lines sent to Mira after a quiet period
			GDT_Url::make('lup_dog_backlog_url')->initial('')->allowAll(false), # PyGDO queue endpoint for quiet room transcripts
			GDT_Checkbox::make('lup_ticket_engine')->initial('0'), # Need to purchase tickets for a room first?
			GDT_Checkbox::make('lup_profile_likes_guests')->initial('0'), # Guests may not like users
			GDT_Credits::make('room_cost')->initial('0'), # One-time cost for creating a room
			GDT_Credits::make('room_cost_view')->initial('0'), # Cost per additional visibility unit
			GDT_Length::make('room_cost_view_unit')->initial('0.500'), # Visibility billing unit in km
			GDT_Credits::make('lup_credits_view_km')->initial('250'), # Credits per added km of room visibility
			GDT_Credits::make('lup_shout_cost_per_km')->initial('150'), # Credits for each kilometre of shout radius
			GDT_Length::make('room_tolerance')->initial('0.064'), # GPS tolerance around room polygons in km
			GDT_Length::make('room_leave_tolerance')->initial('0.640'), # GPS tolerance before automatically leaving a room in km
			GDT_Credits::make('lup_tolerance_credits_per_km')->initial('10000'), # Credits for one kilometre of personal chat tolerance
			GDT_Velocity::make('lup_join_velocity')->min(0.0)->max(1000.0)->initial('10.0'), # km/h
			GDT_Length::make('lup_cuddle_range')->initial('0.100'), # Cuddle range in km
			GDT_Duration::make('lup_cuddle_token_ttl')->initial('2m')->min(30)->max(900),
			GDT_UInt::make('lup_num_top_comments')->initial('3')->max(100), # Num Top comments in Room detail.
			GDT_UInt::make('lup_graph_width')->initial('512')->min(32)->max(4096),
			GDT_UInt::make('lup_graph_height')->initial('392')->min(32)->max(4096),
            GDT_String::make('lup_wechall_flag1')->initial('FLAG_0x1337')->pattern('/[A-Z_0-9x]/'),
		];
	}

	public function cfgMaxLocations(): int { return (int)$this->getConfigValue('lup_max_locations'); }
	public function cfgConnectorSecret(): string { return $this->getConfigVar('lup_connector_secret'); }
	public function isSecretCorrect(string $secret): bool { return hash_equals($this->cfgConnectorSecret(), $secret); }
	public function cfgDogBacklog(): int { return (int)$this->getConfigValue('lup_dog_backlog'); }
	public function cfgDogBacklogURL(): string { return $this->getConfigVar('lup_dog_backlog_url'); }
	public function cfgToleranceCreditsPerKM(): int { return (int)$this->getConfigValue('lup_tolerance_credits_per_km'); }

    public function getUserConfig(): array
    {
        return [
            GDT_UInt::make('lup_cuddles')->icon('trophy')->notNull()->initial('0'),
			GDT_Length::make('tolerance_boost')->initial('0')->min(0)->max(10), # Personally purchased join-range in km
        ];
    }

    public function getACLDefaults(): array
	{
		return [
			'lup_state' => [GDT_ACLRelation::MEMBERS, '0', null],
			'lup_city' => [GDT_ACLRelation::MEMBERS, '0', null],

            'lup_course_visible' => [GDT_ACLRelation::MEMBERS, '0', null],
            'lup_cuddles_visible' => [GDT_ACLRelation::FRIENDS, '0', null],
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
			'lup_religion' => [GDT_ACLRelation::MEMBERS, '0', null],
		];
	}

	public function getUserSettings(): array
	{
		return [
			GDT_Divider::make('div_general'),
			GDT_String::make('lup_status')->icon('info'),
			GDT_Checkbox::make('lup_profile_outside_visible')->initial('1'),

			GDT_Divider::make('div_location'),
			GDT_String::make('lup_state')->icon('country'),
			GDT_String::make('lup_city')->icon('city'),

			GDT_Divider::make('div_contact'),
            GDT_ACLRelation::make('lup_course_visible')->hidden(),
            GDT_ACLRelation::make('lup_cuddles_visible')->hidden(),

			GDT_Divider::make('div_sexy'),
			GDT_EyeColor::make('lup_eyecolor'),
			GDT_PersonHeight::make('lup_height'),
			GDT_RelationInterest::make('lup_interest'),
			GDT_SexualOrientation::make('lup_sexo'),

			GDT_Divider::make('div_habits'),
			GDT_Pet::make('lup_has_pet'),
			GDT_Enum::make('lup_drinks')->icon('wine')->enumValues('lup_drink_yes', 'lup_drink_sometimes', 'lup_drink_never')->emptyLabel('not_specified'),
			GDT_Enum::make('lup_smokes')->icon('smoke')->enumValues('lup_smokes_yes', 'lup_smokes_no_care', 'lup_smokes_no', 'lup_smokes_no_way')->emptyLabel('not_specified'),
			GDT_Enum::make('lup_sporty')->icon('dumbbell')->enumValues('lup_sporty', 'lup_unsporty')->emptyLabel('not_specified'),
			GDT_Religion::make('lup_religion'),
		];
	}

	public function href_administrate_module(): ?string { return href('LinkUUp', 'Admin'); }

	private bool $backendScriptsIncluded = false;

	private function includeBackendScripts(): void
	{
		if ($this->backendScriptsIncluded) return;
		$this->backendScriptsIncluded = true;
		\GDO\Core\GDT_Template::$THEMES = ['lup-backend' => $this->filePath('thm/lup/')] + \GDO\Core\GDT_Template::$THEMES;
		$this->addJS('js/lup-backend-location-status.js');
		$this->addCSS('css/lup.css');
		$this->addCSS('css/lup-arrival-flow.css');
		$this->addJS('js/lup-welcome.js');
		$this->addJS('js/lup-admin-sidebar.js');
		// Bootstrap5 owns the current sidebar. The legacy drawer helper targets
		// an older theme and can create a competing toggle on mixed backend pages.
		$this->addCSS('css/lup-arrival-refresh.css');
		$this->addJS('js/lup-arrival-refresh.js');
		$this->addJS('js/lup-world-journey.js');
        $this->addJS('js/lup-connected-route.js');
		$this->addJS('js/lup-scroll-finale.js');
        $this->addJS('js/lup-living-background.js');
        $this->addJS('js/lup-backend-shell.js');
		$this->addCSS('css/lup-world-journey.css');
		$this->addCSS('css/lup-backend-shell.css');
		$this->addCSS('css/lup-backend-views.css');
		$this->addCSS('css/lup-backend-atlas.css');
		$this->addCSS('css/lup-scroll-adventure.css');
		$this->addCSS('css/lup-scroll-finale.css');
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
		GDT_Page::instance()->topBar()->addFieldFirst(
			GDT_Link::make('lup_home')->href(href('LinkUUp', 'Welcome')));

		if (GDO_User::current()->isStaff())
		{
			GDT_Page::$INSTANCE->rightBar()->addField(GDT_Link::make('lup_staff')->icon('work')->href(href('LinkUUp', 'Main')));
		}

		GDT_Page::instance()->leftBar()->addField(GDT_Link::make('lup_welcome')->href(href('LinkUUp', 'Welcome')));
		$user = GDO_User::current();
		if ($user->gdoVar('user_type') === 'member' && $user->gdoVar('user_deleted') === null)
		{
			GDT_Page::instance()->leftBar()->addField(GDT_Link::make('operator_apply')->textRaw('Betrieb anmelden / Antragsstatus')->href(href('LinkUUp', 'OperatorApply')));
			if ($user->isAdmin())
			{
				GDT_Page::instance()->leftBar()->addField(GDT_Link::make('operator_review')->textRaw('Betriebsanträge prüfen')->href(href('LinkUUp', 'OperatorReview')));
			}
		}

	}

	public function cfgAllowGuestQuery(): bool { return $this->getConfigValue('lup_guest_query'); }

	public function cfgOpenQuery(): bool { return $this->getConfigValue('lup_open_query'); }

	public function cfgOnlyOneChat(): bool { return $this->getConfigValue('lup_only_one_chat'); }
	public function cfgMessageBufferSize(): int { return (int)$this->getConfigValue('lup_msg_bufsize'); }

    public function cfgCuddleRange(): float { return (float)$this->getConfigValue('lup_cuddle_range'); }
	public function cfgCuddleTokenTTL(): int { return (int)$this->getConfigValue('lup_cuddle_token_ttl'); }

	################
	### Settings ###
	################

	public function cfgTicketEngine(): bool { return $this->getConfigValue('lup_ticket_engine'); }

	public function cfgProfileLikeGuests(): bool { return $this->getConfigValue('lup_profile_likes_guests'); }
	public function cfgRoomCost(): int { return (int)$this->getConfigValue('room_cost'); }
	public function cfgRoomCostView(): int { return (int)$this->getConfigValue('room_cost_view'); }
	public function cfgRoomCostViewUnit(): float { return (float)$this->getConfigValue('room_cost_view_unit'); }
	public function cfgCreditsViewKM(): int { return (int)$this->getConfigValue('lup_credits_view_km'); }
	public function cfgShoutCostPerKM(): int { return (int)$this->getConfigValue('lup_shout_cost_per_km'); }
	public function cfgRoomTolerance(): float { return (float)$this->getConfigValue('room_tolerance'); }
	public function cfgRoomLeaveTolerance(): float { return (float)$this->getConfigValue('room_leave_tolerance'); }
	public function cfgJoinVelocity(): float { return (float)$this->getConfigValue('lup_join_velocity'); }

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
		if (BackendLayout::supports($method))
		{
			$this->includeBackendScripts();
		}
		// Public display data only. Checkout remains authoritative for tax and fees.
		if (BackendLayout::supports($method) && GDO_User::current()->isAuthenticated() &&
			$method instanceof \GDO\PaymentCredits\Method\OrderCredits)
		{
			$credits = \GDO\PaymentCredits\Module_PaymentCredits::instance();
			\GDO\Core\Website::addHead('<meta name="lup-credits-pricing" content="' . htmlspecialchars(json_encode([
				'unitPrice' => (float)$credits->cfgConversionRateToCurrency(),
				'currency' => \GDO\Payment\GDT_Money::$CURRENCY,
				'minCredits' => (int)$credits->cfgMinPurchaseCredits(),
			], JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8') . '">');
		}
		# Redirect to login if not authenticated
		if (Application::instance()->isWebserver())
		{
			if (!GDO_User::current()->isAuthenticated())
			{

				$allowed = [
					"GDO\\Login\\Method\\Form",
					"GDO\\GoogleAuth\\Method\\Auth",
					"GDO\\GoogleAuth\\Method\\Callback",
					"GDO\\Avatar\\Method\\Image",
					"GDO\\Avatar\\Method\\ForUser",
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
					"GDO\\Register\\Method\\TOS",
					"GDO\\Recovery\\Method\\Form",
					"GDO\\Recovery\\Method\\Change",
					"GDO\\Websocket\\Method\\GetSecret",
					"GDO\\Date\\Method\\Timezone",
					"GDO\\Date\\Method\\Timezones",
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
					"GDO\\LinkUUp\\Method\\FromDog",
					"GDO\\LinkUUp\\Method\\Main",
					"GDO\\LinkUUp\\Method\\QRForRoom",
					"GDO\\LinkUUp\\Method\\Welcome",
					"GDO\\Maps\\Method\\Record",
					"GDO\\Core\\Method\\Error",
				];
				$class = $method->gdoClassName();
				if (!in_array($class, $allowed, true))
				{
					$hrefLogin = href('Login', 'Form');
					throw new GDO_RedirectError('lup_login_required', [$hrefLogin], $hrefLogin, GDT_Redirect::CODE);
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

	public function cfgAppUrl()
    {
        return defined('GDO_LUP_REVIEW_APP_URL') ? GDO_LUP_REVIEW_APP_URL : $this->getConfigVar('lup_app_url');
    }

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
		// Retain the routing field without rendering an extra login-form action.
		$a->addField(GDT_Link::make('lup_back_to_backend')
			->href(href('LinkUUp', 'Welcome'))->text('lup_back_to_backend')->hidden());
	}

	/** Keep registration from becoming a dead-end in the back office as well. */
	public function hookRegisterForm(GDT_Form $form)
	{
		$form->actions()->addField(GDT_Link::make('lup_back_to_backend')
			->href(href('LinkUUp', 'Welcome'))->text('lup_back_to_backend')->hidden());
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
