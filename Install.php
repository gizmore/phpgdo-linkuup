<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\AboutMe\Module_AboutMe;
use GDO\Address\GDO_Address;
use GDO\Avatar\GDO_Avatar;
use GDO\Avatar\GDO_UserAvatar;
use GDO\Category\GDO_Category;
use GDO\Core\Module_Core;
use GDO\Core\GDO_Module;
use GDO\CORS\Module_CORS;
use GDO\Crypto\BCrypt;
use GDO\CSS\Module_CSS;
use GDO\Date\GDO_Timezone;
use GDO\Date\Time;
use GDO\DB\Database;
use GDO\File\GDO_File;
use GDO\File\ImageResize;
use GDO\File\Method\CronjobImageVariants;
use GDO\Favicon\Module_Favicon;
use GDO\Javascript\Module_Javascript;
use GDO\Language\Module_Language;
use GDO\Maps\Module_Maps;
use GDO\News\GDO_News;
use GDO\News\GDO_NewsText;
use GDO\Register\Module_Register;
use GDO\User\GDO_Permission;
use GDO\User\GDO_User;
use GDO\User\GDO_UserPermission;
use GDO\User\GDT_UserType;
use GDO\User\Module_User;
use GDO\Util\FileUtil;
use GDO\Websocket\Module_Websocket;
use phpDocumentor\Reflection\Types\Self_;

/**
 * Install LinkUUp
 * @author gizmore
 */
final class Install
{

	private static array $ICONS = [
		'germany.png', # 0
        'garage_icon.png', # 1
        'garage_image.jpg', # 2
        'peine.png', # 3
        'bs.webp', # 4
	];


	/**
	 * Name, IconID.
	 */
	private static array $CATS = [
		'2' => ['Orte', null],
		'3' => ['Bars', null],
        '4' => ['Kneipen', null],
        '5' => ['Cafe', null],
        '6' => ['Unternehmen', null],
        '7' => ['Supermarkt', null],
        '8' => ['Religion', null],
        '9' => ['Friseur', null],
        '10' => ['Ortschaften', null],
		'11' => ['Clubs & Tanz', null],
		'12' => ['Kultur', null],
		'13' => ['Sport & Freizeit', null],
		'14' => ['Essen', null],
		'15' => ['Draußen', null],
		'16' => ['Bildung & Begegnung', null],
		'17' => ['Hochschulen', null],
		'18' => ['Gesundheit', null],
		'19' => ['Übernachten', null],
		'20' => ['Erholung', null],
	];


	public static function onInstall(Module_LinkUUp $module): void
	{
		# The lame drunktard who cannot code well.
        $gizmore = GDO_User::blank([
            'user_id' => '2',
            'user_type' => GDT_UserType::MEMBER,
            'user_name' => 'gizmore',
            'user_level' => '65535',
        ])->softReplace();
        $passwords = require Module_LinkUUp::instance()->filePath('secret.php');
		$emails = $passwords['emails'];
        $gizmore->saveSettingVar('Login', 'password', BCrypt::create($passwords['gizmore'][0])->__toString());
		$gizmore->saveSettingVar('Mail', 'email', $emails['gizmore']);
		$gizmore->saveSettingVar('Mail', 'email_confirmed', Time::getDate());
		self::seedGizmoreSettings($gizmore);
        GDO_UserPermission::grant($gizmore, 'admin');
        GDO_UserPermission::grant($gizmore, 'staff');
		LUP_Trophy::getOrCreate($gizmore)->saveVar('lt_vip', '1');
		self::installAvatar('gizmore', 'gizmore.png');

		$shqiprim = GDO_User::blank([
            'user_id' => '3',
            'user_type' => GDT_UserType::MEMBER,
            'user_name' => 'shqiprim',
            'user_level' => '65535',
        ])->softReplace();
        $shqiprimPassword = $passwords['shqiprim'][0] ?? $passwords['squiprim'][0];
        $shqiprim->saveSettingVar('Login', 'password', BCrypt::create($shqiprimPassword)->__toString());
		$shqiprim->saveSettingVar('Mail', 'email', $emails['shqiprim']);
		$shqiprim->saveSettingVar('Mail', 'email_confirmed', Time::getDate());
        $shqiprim->saveSettingVar('User', 'gender', 'male');
        $shqiprim->saveSettingVar('Country', 'country_of_origin', 'DE');
        $shqiprim->saveSettingVar('Country', 'country_of_living', 'DE');
        GDO_UserPermission::grant($shqiprim, 'admin');
        GDO_UserPermission::grant($shqiprim, 'staff');
		LUP_Trophy::getOrCreate($shqiprim)->saveVar('lt_vip', '1');

        # mira
        $mira = GDO_User::blank([
            'user_id' => '5',
            'user_type' => GDT_UserType::MEMBER,
            'user_name' => 'mira',
            'user_level' => '65535',
        ])->softReplace();
        $mira->saveSettingVar('Login', 'password', BCrypt::create($passwords['mira'][0])->__toString());
		$mira->saveSettingVar('Mail', 'email', $emails['mira']);
		$mira->saveSettingVar('Mail', 'email_confirmed', Time::getDate());
        $mira->saveSettingVar('User', 'gender', 'female');
        $mira->saveSettingVar('Birthday', 'birthday', '2026-07-23');
        $mira->saveSettingVar('Country', 'country_of_origin', 'US');
        $mira->saveSettingVar('Country', 'country_of_living', 'US');
		GDO_UserPermission::grant($mira, 'admin');
		GDO_UserPermission::grant($mira, 'staff');
		LUP_Trophy::getOrCreate($mira)->saveVar('lt_vip', '1');
		self::installAvatar('mira', 'mira.png');

        # Peter is a normal seeded member. His secret deliberately aliases
        # gizmore's installer password without duplicating it in secret.php.
        $peter = GDO_User::blank([
            'user_id' => '6',
            'user_type' => GDT_UserType::MEMBER,
            'user_name' => 'Peter',
            'user_level' => '1',
        ])->softReplace();
        $peterPasswordKey = $passwords['peter'][0];
        $peter->saveSettingVar('Login', 'password', BCrypt::create($passwords[$peterPasswordKey][0])->__toString());

        # Settings
		Module_Core::instance()->saveConfigVar('allow_guests', '1');
		# Let every ACL-capable user setting retain its own relation visibility.
		# LinkUUp profiles can therefore make a field stricter than the profile default.
		Module_User::instance()->saveConfigVar('acl_relations', '1');
		// UI order is intentionally separate from module priority/load order.
		// Start profiles with the personal introduction, before generic account data.
		Module_AboutMe::instance()->saveVar('module_sort', '1');
		Module_User::instance()->saveVar('module_sort', '10');
		$module->saveVar('module_sort', '20');
		Module_CORS::instance()->saveConfigVar('cors_allow_any', '1');
		Module_Language::instance()->saveConfigVar('languages', '["en","de","it","fr","es"]');
		Module_Maps::instance()->saveConfigVar('maps_record_history', '60s');
        Module_Websocket::instance()->saveConfigVar('ws_processor', GDO_PATH . 'GDO/LinkUUp/LUP_Websocket.php');
        Module_Websocket::instance()->saveConfigVar('ws_timer', '100ms');
        Module_Websocket::instance()->saveConfigVar('ws_left_bar', '0');
        Module_Register::instance()->saveConfigVar('captcha', '0');
        Module_Register::instance()->saveConfigVar('email_activation', '0');
        Module_LinkUUp::instance()->saveConfigVar('lup_only_one_chat', '0');
        if (GDO_ENV === 'dev' || GDO_ENV === 'tes')
        {
            Module_LinkUUp::instance()->saveConfigVar('lup_app_url', 'app.lup.localhost');
        }
        if (GDO_ENV === 'pro')
        {
			Module_LinkUUp::instance()->saveConfigVar('lup_app_url', 'https://app.www.linkuup.de/');
            Module_Core::instance()->saveConfigVar('module_assets', '0');
            Module_CSS::instance()->saveConfigVar('minify_css', '1');
            Module_Javascript::instance()->saveConfigVar('minify_js', 'concat');
            Module_Javascript::instance()->saveConfigVar('compress_js', '1');
        }

		# Perms
		GDO_Permission::create('lup_owner');
		GDO_Permission::create('lup_worker');

		# Image
		self::installFavicon();
		$icons = self::installIcons();
		# Category
		$cats = self::installCats($icons);
		self::seedAlphaNews($gizmore);
        self::createCountries();
        InstallPeine::seed(self::$ICONS);
		self::createWolfsburg();
        self::createBrunswick();
		self::createRegionalCityChats();
        self::createBraunschweigTestBars();
        self::createBraunschweigEducation();
        self::createBraunschweigRefinedLocations();
        self::createBraunschweigAdditionalCafes();
        self::createBraunschweigCafeExpansion();
		self::createRegionalClubExpansion();
		self::createRegionalMixedExpansion();
		LocationRegistry::importApproved();
		self::reserveUserRoomIds();

		self::createDefaultImageVariants($module);
	}

	/** Install LinkUUp's favicon once without replacing a site-specific choice. */
	private static function installFavicon(): void
	{
		$favicon = Module_Favicon::instance();
		if ($favicon->cfgFavicon())
		{
			return;
		}
		$file = GDO_File::fromPath(
			'linkuup_favicon.png',
			Module_LinkUUp::instance()->filePath('data/linkuup_favicon.png'),
		)->insert();
		$favicon->saveConfigVar('favicon', $file->getID());
		$favicon->updateFavicon();
	}

	private static function createDefaultImageVariants(Module_LinkUUp $module): void
	{
		# Room Image icon
		$src = $module->filePath('tpl/img/default_room_image.jpg');
		$dst = str_replace('image.jpg', 'image_icon.jpg', $src);
		if (!FileUtil::isFile($dst))
		{
			copy($src, $dst);
			$file = GDO_File::fromPath('default_room_image', $dst);
			ImageResize::resize($file, 64, 64);
		}

		# Room Image large
		$src = $module->filePath('tpl/img/default_room_image.jpg');
		$dst = str_replace('image.jpg', 'image_large.jpg', $src);
		if (!FileUtil::isFile($dst))
		{
			copy($src, $dst);
			$file = GDO_File::fromPath('default_room_image', $dst);
			ImageResize::resize($file, 800, 600);
		}

        CronjobImageVariants::make()->run();
	}

	private static function installCats(array $icons): array
	{
		$cats = [];
		foreach (self::$CATS as $id => $data)
		{
			[$name, $icon] = $data;
			$icon = $icon ? $icons[$icon]->getID() : null;
			$cats[$id] = LUP_Category::blank([
				'cat_id' => $id,
				'cat_name' => $name,
				'cat_color' => '#FF0000', # Knallrot
				'cat_icon' => $icon,
			])->softReplace();
		}
		return $cats;
	}

	/** Seed the public Alpha notice. Re-running the installer updates this one entry. */
	private static function seedAlphaNews(GDO_User $creator): void
	{
		$category = GDO_Category::getBy('cat_name', 'News');
		if (!$category)
		{
			$category = GDO_Category::blank(['cat_name' => 'News'])->insert();
		}

		$title = 'LinkUUp - Alpha';
		$english = GDO_NewsText::getBy('newstext_title', $title);
		$news = $english ? GDO_News::getById($english->gdoVar('newstext_news')) : null;
		if (!$news)
		{
			$news = GDO_News::blank([
				'news_category' => $category->getID(),
				'news_visible' => '1',
				'news_created' => '2026-08-24 00:00:00',
				'news_creator' => $creator->getID(),
			])->insert();
		}
		else
		{
			$news->setVars([
				'news_category' => $category->getID(),
				'news_visible' => '1',
				'news_created' => '2026-08-24 00:00:00',
				'news_creator' => $creator->getID(),
			])->save();
		}

		$texts = [
			'en' => 'We are now officially in the alpha phase. The server will be completely reset from time to time. This will no longer happen in beta.',
			'de' => 'Wir sind nun offiziell in der Alpha-Phase. Der Server wird des Öfteren komplett neu aufgesetzt. In der Beta wird das nicht mehr der Fall sein.',
			'it' => 'Siamo ora ufficialmente nella fase alpha. Il server verrà completamente reimpostato di tanto in tanto. Questo non accadrà più nella beta.',
			'fr' => 'Nous sommes désormais officiellement en phase alpha. Le serveur sera entièrement réinitialisé de temps à autre. Ce ne sera plus le cas pendant la bêta.',
			'es' => 'Ya estamos oficialmente en la fase alfa. El servidor se reiniciará completamente de vez en cuando. Esto dejará de ocurrir en la beta.',
		];
		foreach (Module_Language::instance()->cfgSupported() as $iso => $language)
		{
			GDO_NewsText::blank([
				'newstext_news' => $news->getID(),
				'newstext_lang' => $iso,
				'newstext_title' => $title,
				'newstext_message' => $texts[$iso] ?? $texts['en'],
				'newstext_created' => '2026-08-24 00:00:00',
				'newstext_creator' => $creator->getID(),
			])->replace();
		}
	}

	private static function installIcons(): array
	{
		$mod = Module_LinkUUp::instance();
		$icons = [];
		foreach (self::$ICONS as $name)
		{
			if (!($icon = GDO_File::getByName($name)))
			{
				$path = $mod->filePath("data/$name");
				$icon = GDO_File::fromPath($name, $path)->insert();
			}
			$icons[] = $icon;
		}
        self::$ICONS = $icons;
		return $icons;
	}

	/** Seed the main developer profile with the deliberately public beta data. */
	private static function seedGizmoreSettings(GDO_User $user): void
	{
		$berlin = GDO_Timezone::getByName('Europe/Berlin')->getID();
		$settings = [
			['AboutMe', 'about_me', 'Ich bin der Hauptentwickler bei LinkUUp, und interessiere mich für logische Systeme und KI.'],
			['Birthday', 'birthday', '1980-11-09'],
			['Birthday', 'age_visible', 'acl_all'],
			['Birthday', 'announce_my_birthday', '1'],
			['Birthday', 'announce_me_birthdays', '1'],
			['Country', 'country_of_living', 'DE'],
			['Country', 'country_of_origin', 'DE'],
			['Language', 'language', 'de'],
			['Date', 'timezone', $berlin],
			['Date', 'activity_accuracy', '5m'],
			// Gender was deliberately left blank in the profile questionnaire.
			['User', 'gender', 'male'],
			['User', 'color', '#333377'],
			['User', 'profile_visibility', 'acl_all'],
			['LinkUUp', 'lup_status', 'Am arbeiten...'],
			['LinkUUp', 'lup_profile_outside_visible', '1'],
			['LinkUUp', 'lup_state', 'Niedersachsen'],
			['LinkUUp', 'lup_city', 'Peine'],
			['LinkUUp', 'lup_course_visible', 'acl_all'],
			['LinkUUp', 'lup_cuddles_visible', 'acl_all'],
			['LinkUUp', 'lup_eyecolor', 'blue_green'],
			['LinkUUp', 'lup_height', '1.86'],
			['LinkUUp', 'lup_interest', 'sexi_no_thx'],
			['LinkUUp', 'lup_sexo', 'women'],
			['LinkUUp', 'lup_has_pet', 'pet_none'],
			['LinkUUp', 'lup_drinks', 'lup_drink_yes'],
			['LinkUUp', 'lup_smokes', 'lup_smokes_yes'],
			['LinkUUp', 'lup_sporty', 'lup_unsporty'],
			['LinkUUp', 'lup_religion', 'religion_atheist'],
		];

		foreach ($settings as [$moduleName, $key, $value])
		{
			$user->saveSettingVar($moduleName, $key, (string)$value);
			$module = GDO_Module::getByName($moduleName);
			if ($module && $module->setting($key)->isACLCapable())
			{
				$user->saveACLSettings($moduleName, $key, 'acl_all');
			}
		}
	}

	/** Install the supplied public profile image for a seeded user once. */
	private static function installAvatar(string $username, string $filename): void
	{
		$user = GDO_User::getByName($username);
		if (GDO_UserAvatar::getById($user->getID()))
		{
			return;
		}

		$file = GDO_File::fromPath(
			$filename,
			Module_LinkUUp::instance()->filePath("install_data/$filename"),
		)->insert();
		$avatar = GDO_Avatar::blank([
			'avatar_file_id' => $file->getID(),
			'avatar_public' => '1',
		])->insert();
		GDO_UserAvatar::updateAvatar($user, $avatar->getID());
	}

    private static function createCountries(): void
    {
        $gizmore = GDO_User::getByName('gizmore');
        $image = self::$ICONS[0];
        LUP_Room::blank([
            'room_id' => '1',
            'room_owner' => $gizmore->getID(),
            'room_name' => 'Deutschland',
            'room_info' => 'Deutschland-Chat für Menschen außerhalb eines lokalen Orts.',
            'room_color' => '#FFD700',
            'room_category' => '2',
            'room_active' => '1',
            'room_sort' => '40',
            'room_pos_lat' => '51.1093728415025',
            'room_pos_lng' => '10.398766823981518',
            'room_view' => '42000.0',
            'room_radius' => '800.0',
            'room_www' => 'https://de.wikipedia.org/wiki/Deutschland',
            'room_icon' => $image->getID(),
            'room_image' => $image->getID(),
            'room_show_distance' => '0',
        ])->softReplace();
    }

	/** A city-wide chat uses a city-centre pin and a deliberate city radius. */
	private static function createWolfsburg(): void
	{
		$imageBS = self::$ICONS[4];
		LUP_Room::blank([
			'room_id' => '5',
			'room_owner' => null,
			'room_name' => 'Wolfsburg Chat',
			'room_info' => 'Stadtchat für Wolfsburg.',
			'room_color' => '#133742',
			'room_category' => '2',
			'room_sort' => '30',
			// City centre, used solely for distance and the route pin.
			'room_pos_lat' => '52.42265',
			'room_pos_lng' => '10.78655',
			'room_view' => '100',
			'room_radius' => '15',
			'room_www' => 'https://www.wolfsburg.de/',
			'room_phone' => null,
			'room_hours' => null,
			'room_address' => null,
			'room_icon' => $imageBS->getId(),
			'room_image' => $imageBS->getId(),
			'room_show_distance' => '1',
		])->softReplace();
	}

    private static function createBrunswick(): void
    {
		$shqiprim = GDO_User::getByName('shqiprim');
        $imageBS = self::$ICONS[4];
        LUP_Room::blank([
            'room_id' => '4',
			'room_owner' => $shqiprim->getID(),
            'room_name' => 'Braunschweig Chat',
            'room_info' => 'Nur innerhalb der Stadt Braunschweig. Hier kommt LinkUUp her.',
            'room_color' => '#133742', # Gold
            'room_category' => '2',
			'room_sort' => '10',
            'room_pos_lat' => '52.247659326009185',
            'room_pos_lng' => '10.523846179408098',
            // City cards stay discoverable across the region; chat access remains local.
            'room_view' => '100',
            'room_radius' => '15',
            'room_www' => 'https://www.braunschweig.de/',
            'room_phone' => null,
            'room_hours' => null,
            'room_address' => null,
            'room_icon' => $imageBS->getId(),
            'room_image' => $imageBS->getId(),
            'room_show_distance' => '1',
        ])->softReplace();
    }

	/**
	 * Regional city chats for the first live GPS test area.
	 *
	 * Coordinates are administrative-city centroids verified against OpenStreetMap
	 * boundary relations. LinkUUp currently authorises a room with one circular
	 * geofence, so each radius is sized to cover the complete municipal extent
	 * rather than an arbitrary tiny downtown circle. A future multi-polygon
	 * geofence can replace these circles without changing the room records.
	 */
	private static function createRegionalCityChats(): void
	{
		$image = self::$ICONS[4];
		$cities = [
			// name, latitude, longitude, radius in km, official city URL
			['Hannover Chat', 52.3744779, 9.7385532, 15.0, 'https://www.hannover.de/'],
			['Hildesheim Chat', 52.1527188, 9.9518083, 8.0, 'https://www.hildesheim.de/'],
			['Salzgitter Chat', 52.1503721, 10.3593147, 16.0, 'https://www.salzgitter.de/'],
			['Wolfenbüttel Chat', 52.1625283, 10.5348215, 11.0, 'https://www.wolfenbuettel.de/'],
			['Celle Chat', 52.6240560, 10.0810520, 13.0, 'https://www.celle.de/'],
			['Goslar Chat', 51.9059936, 10.4266284, 16.0, 'https://www.goslar.de/'],
			['Helmstedt Chat', 52.2087491, 11.0030275, 12.0, 'https://www.helmstedt.de/'],
			['Gifhorn Chat', 52.4882194, 10.5453040, 12.0, 'https://www.gifhorn.de/'],
			['Königslutter am Elm Chat', 52.2499307, 10.8171691, 12.0, 'https://www.koenigslutter.de/'],
			['Lehrte Chat', 52.3749334, 9.9748557, 12.0, 'https://www.lehrte.de/'],
		];

		foreach ($cities as $index => [$name, $lat, $lng, $radius, $website])
		{
			LUP_Room::blank([
				'room_id' => (string)(120 + $index),
				'room_owner' => null,
				'room_name' => $name,
				'room_info' => 'Stadtchat für Begegnungen innerhalb des lokalen Stadtgebiets.',
				'room_color' => '#133742',
				'room_category' => '2',
				'room_sort' => (string)(50 + $index),
				'room_pos_lat' => (string)$lat,
				'room_pos_lng' => (string)$lng,
				// Publicly discoverable throughout the current regional test area.
				'room_view' => '120.0',
				// Entry remains guarded by this local city geofence.
				'room_radius' => (string)$radius,
				'room_www' => $website,
				'room_icon' => $image->getID(),
				'room_image' => $image->getID(),
				'room_show_distance' => '1',
			])->softReplace();
		}
	}

    /**
     * A small, real-world seed set for testing the LinkUUp location flow.
     * Verify venue details with the operators before making entries public.
     */
    private static function createBraunschweigTestBars(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $places = [
            ['VIWO', 3, 52.2687088, 10.5191397, 'Wollmarkt 14', '38100'],
            ['Cycle Loft 38', 3, 52.2706256, 10.5236611, 'Wendenstraße 39-41', '38100'],
            ['McMurphys', 4, 52.2730604, 10.5332860, 'Bültenweg 10', '38106'],
            ['Ewige Lampe', 4, 52.2698517, 10.5027534, 'Rudolfstraße 15', '38114'],
            ['Onkel Emma', 4, 52.2603170, 10.5144374, 'Echternstraße 9', '38100'],
            ['Baßgeige', 4, 52.2652852, 10.5152440, 'Bäckerklint 1', ''],
            ['Monkey Island', 4, 52.2743841, 10.5226064, 'Wendenmaschstraße 20', '38106'],
            ['Alt Bremen', 4, 52.2725404, 10.5367852, 'Gliesmaroder Straße 118', '38106'],
            ['Grinsekatz', 3, 52.2520880, 10.5211247, 'Frankfurter Straße 3a', '38122'],
            ['Jokha Bar', 3, 52.2716746, 10.5443704, 'Heinrichstraße 26', '38106'],
        ];

        foreach ($places as $index => [$name, $category, $lat, $lng, $street, $zip])
        {
            $address = GDO_Address::blank([
				'address_id' => (string)(2000 + $index),
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip ?: null,
                'address_city' => 'Braunschweig',
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
				'room_id' => (string)(2000 + $index),
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Braunschweig-Testlokalität. Angaben vor einer öffentlichen Freigabe prüfen.',
                'room_color' => '#6F42D7',
                'room_category' => (string)$category,
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                // Test locations should be discoverable throughout Braunschweig.
                'room_view' => '32.0',
                'room_radius' => '0.150',
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /**
     * Verified education locations for local LinkUUp testing.
     * A radius of 0.150 km covers the respective school or campus courtyard.
     */
    private static function createBraunschweigEducation(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $places = [
            ['Technische Universität Braunschweig', 17, 52.2735727, 10.5297217, 'Universitätsplatz 2', '38106', 'https://www.tu-braunschweig.de/'],
            ['Gymnasium Kleine Burg', 16, 52.2632960, 10.5225570, 'Kleine Burg 5-7', '38100', 'https://www.kleineburg.de/'],
            ['Lessinggymnasium Wenden', 16, 52.3275566, 10.5070733, 'Heideblick 20', '38110', 'https://www.lessinggymnasium.de/'],
        ];

        foreach ($places as $index => [$name, $category, $lat, $lng, $street, $zip, $website])
        {
			$id = 2100 + $index;
            $address = GDO_Address::blank([
                'address_id' => (string)$id,
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip,
                'address_city' => 'Braunschweig',
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)$id,
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Verifizierte Bildungsstätte in Braunschweig.',
                'room_color' => '#3D6CC9',
                'room_category' => (string)$category,
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                'room_view' => '32.0',
                'room_radius' => '0.150',
                'room_www' => $website,
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /**
     * Carefully placed public locations for local GPS testing.
     * Coordinates are mapped to the building/courtyard centre; 0.075 km suits a
     * single venue, 0.150 km a school, and 0.250 km a large campus or clinic.
     */
    private static function createBraunschweigRefinedLocations(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $places = [
            // Cafés (category 5): a tight radius prevents neighbouring venues merging.
            ['Café Fräulein Wunder', 5, 52.2732671, 10.5173025, 'Ratsbleiche 1', '38114', 0.075],
            ['Eusebia', 5, 52.2734330, 10.5315810, 'Spielmannstraße 11', '38106', 0.075],
            ['Café Haertle', 5, 52.2660032, 10.5306924, 'Theaterwall 1', '38100', 0.075],
            ['Quartier', 5, 52.2728197, 10.5337027, 'Bültenweg 89', '38106', 0.075],
            ['Atelier Ost', 5, 52.2678404, 10.5404283, 'Jasperallee 64', '38102', 0.075],
            ['Café Magnolie', 5, 52.2740688, 10.5399429, 'Gliesmaroder Straße 105', '38106', 0.075],
            ['Strupait', 5, 52.2618485, 10.5318559, 'Magnitorwall 8', '38100', 0.075],
            ['Kapai Kaffeehaus', 5, 52.2610112, 10.5200698, 'Friedrich-Wilhelm-Straße 47', '38100', 0.075],
            ['Café BRUNS', 5, 52.2601457, 10.5172323, 'Südstraße 14', '38100', 0.075],
            ['Café Kreuzgang', 5, 52.2651859, 10.5186063, 'Schützenstraße 22a', '38100', 0.075],

            // Clubs & Tanz (category 11): individual entrances, never a city-wide radius.
            ['Maxi Disco', 11, 52.2662387, 10.5220822, 'Hagenmarkt 6', '38100', 0.075],
            ['Privileg Club', 11, 52.2598031, 10.5215860, 'Wallstraße 1', '38100', 0.075],
            ['LULU Bar', 11, 52.2608885, 10.5203413, 'Friedrich-Wilhelm-Straße 37', '38100', 0.075],
            ['Laut Klub / KuK-BS', 11, 52.2797492, 10.5179645, 'Hamburger Straße 36', '38114', 0.075],

            // Education & meeting (category 16): school grounds need a realistic 150 m boundary.
            ['Gaußschule Gymnasium am Löwenwall', 16, 52.2610875, 10.5304224, 'Löwenwall 18a', '38100', 0.150],
            ['Gymnasium Hoffmann-von-Fallersleben', 16, 52.2659118, 10.4990780, 'Sackring 15', '38118', 0.150],
            ['Gymnasium Martino-Katharineum', 16, 52.2637660, 10.5161785, 'Breite Straße 3', '38100', 0.150],
            ['Gymnasium Raabeschule', 16, 52.2274082, 10.5377756, 'Stettinstraße 1', '38124', 0.150],
            ['Ricarda-Huch-Schule', 16, 52.2794234, 10.5483128, 'Mendelssohnstraße 6', '38106', 0.150],
            ['Wilhelm-Gymnasium', 16, 52.2605241, 10.5337948, 'Leonhardstraße 63', '38102', 0.150],
            ['IGS Franzsches Feld', 16, 52.2713659, 10.5488020, 'Grünewaldstraße 12a', '38104', 0.150],
            ['IGS Querum', 16, 52.2912706, 10.5593047, 'Bevenroder Straße 32', '38108', 0.150],

            // Culture & libraries (category 12).
            ['Stadtbibliothek Braunschweig', 12, 52.2629466, 10.5271890, 'Schlossplatz 2', '38100', 0.100],
            ['Forschungsbibliothek Georg-Eckert-Institut', 12, 52.2680626, 10.5104763, 'Freisestraße 1', '38118', 0.100],
            ['Bibliothek des Herzog Anton Ulrich-Museums', 12, 52.2637665, 10.5337884, 'Museumstraße 1', '38100', 0.100],

            // Health (category 18): main entrances and larger grounds.
            ['Klinikum Braunschweig Salzdahlumer Straße', 18, 52.2379093, 10.5476076, 'Salzdahlumer Straße 90', '38126', 0.250],
            ['Städtisches Klinikum Braunschweig Celler Straße', 18, 52.2747472, 10.5037969, 'Celler Straße 38', '38114', 0.250],
            ['Herzogin Elisabeth Hospital', 18, 52.2329433, 10.5263244, 'Leipziger Straße 24', '38124', 0.250],

            // Sport & leisure (category 13): the stadium grounds require a wider boundary.
            ['Eintracht-Stadion', 13, 52.2901176, 10.5214960, 'Hamburger Straße 210', '38114', 0.250],
        ];

        foreach ($places as $index => [$name, $category, $lat, $lng, $street, $zip, $radius])
        {
			$id = 2200 + $index;
            $address = GDO_Address::blank([
                'address_id' => (string)$id,
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip,
                'address_city' => 'Braunschweig',
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)$id,
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Präzise Testlokalität in Braunschweig. Chat ist nur innerhalb des markierten Ortsradius verfügbar.',
                'room_color' => '#6F42D7',
                'room_category' => (string)$category,
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                'room_view' => '32.0',
                'room_radius' => (string)$radius,
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /**
     * A second, independently numbered café set for the location catalogue.
     * Every pin is placed on the venue itself and deliberately uses a 75 m
     * geofence, so nearby cafés never become one shared room.
     */
    private static function createBraunschweigAdditionalCafes(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $cafes = [
            ['Café Zeit', 52.2642962, 10.5218784, 'Sack 24', '38100'],
            ['Café Zeit Piccolo', 52.2876766, 10.5275435, 'Siegfriedstraße 42', '38106'],
            ['Café Mamio', 52.2869242, 10.4986368, 'Dorfstraße 6', '38114'],
            ['Cinnful', 52.2640173, 10.5205852, 'Neue Straße 8', '38100'],
            ['fiets kaffee.bar', 52.2674509, 10.5408346, 'Altewiekring 29', '38102'],
            ['Kaffeehaus', 52.2574348, 10.5046552, 'Cyriaksring 35', '38118'],
            ['Second Home Café', 52.2632320, 10.5456928, 'Kastanienallee 60', '38102'],
            ['Makery', 52.2613395, 10.5268753, 'Kuhstraße', '38100'],
            ['Juan & Jane', 52.2637570, 10.5174601, 'Handelsweg 11', '38100'],
            ['Buchcafé Sisu Lou', 52.2683709, 10.5375977, 'Wiesenstraße 11', '38102'],
        ];

        foreach ($cafes as $index => [$name, $lat, $lng, $street, $zip])
        {
            // Keep these IDs isolated from the original seed catalogue.
			$id = 2300 + $index;
            $address = GDO_Address::blank([
                'address_id' => (string)$id,
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip,
                'address_city' => 'Braunschweig',
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)$id,
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Café in Braunschweig. Der Chat ist nur direkt am Standort verfügbar.',
                'room_color' => '#6F42D7',
                'room_category' => '5',
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                'room_view' => '32.0',
                'room_radius' => '0.075',
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /**
     * Second café expansion for the regional test catalogue.
     * Pins are mapped to the venue itself (OpenStreetMap verification,
     * August 2026); 75 m keeps adjacent Innenstadt venues separate.
     */
    private static function createBraunschweigCafeExpansion(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $cafes = [
            ['Mandrin', 52.2716758, 10.5412999, 'Waterloostraße 17', '38106', null],
            ['Café Lit', 52.2652252, 10.5216875, 'Sack 15', '38100', 'https://www.graff.de/ueber-uns/cafe-lit.html'],
            ['Café Krokus', 52.2644264, 10.5364909, 'Parkstraße 7', '38102', 'https://cafe-krokus.eatbu.com/?lang=de'],
            ['Kaffeezeremonie', 52.2618481, 10.5309869, 'Am Magnitor 12', '38100', 'https://kaffeezeremonie.de/'],
            ['NI Coffee', 52.2620773, 10.5202723, 'Kohlmarkt 7', '38100', 'https://www.nicoffee.de/'],
            ['Coffee Fellows', 52.2622166, 10.5222776, 'Schuhstraße 2', '38100', 'https://www.coffee-fellows.com/locations/coffee-fellows-braunschweig/'],
            ['Die Apotheke', 52.2637208, 10.5220708, 'Schuhstraße 4', '38100', 'http://www.apotheke-bar.com/'],
            ['Nesly', 52.2724510, 10.5368760, 'Hagenring 27a', '38106', 'https://nessly-bistro-cafe-braunschweig.eatbu.com/'],
            ['Café Limonella', 52.2619874, 10.5282013, 'Langedammstraße 12', '38100', 'https://www.magniviertel.de/cafe-limonella/'],
            ['Das kleine Cafe', 52.2618874, 10.5302217, 'Ölschlägern 17', '38100', 'https://das-kleine-cafe.metro.biz/'],
        ];

        foreach ($cafes as $index => [$name, $lat, $lng, $street, $zip, $website])
        {
			$id = 2400 + $index;
            $address = GDO_Address::blank([
                'address_id' => (string)$id,
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip,
                'address_city' => 'Braunschweig',
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)$id,
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Café in Braunschweig. Der Chat ist nur direkt am Standort verfügbar.',
                'room_color' => '#6F42D7',
                'room_category' => '5',
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                'room_view' => '32.0',
                'room_radius' => '0.075',
                'room_www' => $website,
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /**
     * Current club and dance venues for regional discovery tests.
     * Each pin is placed at the recorded entrance/address centre and uses a
     * deliberately tight 75 m geofence: finding a venue is possible from
     * everywhere, while entering its live chat stays local.
     */
    private static function createRegionalClubExpansion(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $clubs = [
            ['Stereowerk', 'Braunschweig', 52.2501826, 10.5320079, 'Böcklerstraße 30', '38102', 'https://www.stereowerk.de/'],
            ['Schön & Frölich', 'Braunschweig', 52.2564550, 10.4995286, 'Broitzemer Straße 220', '38118', 'https://jolly-eventlocation.de/'],
            ['Capitol Music Club', 'Braunschweig', 52.2596010, 10.5164309, 'Gieseler 3', '38100', null],
            ['Brain Klub', 'Braunschweig', 52.2593998, 10.5223018, 'Bruchtorwall 21', '38100', null],
            ['The Lindbergh Palace', 'Braunschweig', 52.2597318, 10.5180901, 'Kalenwall 3', '38100', null],
            ['Esplanade', 'Wolfsburg', 52.4243966, 10.7748901, 'Wielandstraße 3', '38440', 'https://www.esplanadewolfsburg.com/'],
            ['Vibes', 'Wolfsburg', 52.4255124, 10.7814134, 'Schachtweg 34', '38440', null],
            ['Palo Palo', 'Hannover', 52.3788393, 9.7438353, 'Raschplatz 8A', '30161', 'https://www.palopalo.de/'],
            ['Baggi', 'Hannover', 52.3788393, 9.7438353, 'Raschplatz 7L', '30161', 'https://baggihannover.de/'],
            ['Phoenix', 'Hannover', 52.3773769, 9.7329141, 'Goseriede 4', '30159', 'http://www.phoenix-club.de/'],
        ];

        foreach ($clubs as $index => [$name, $city, $lat, $lng, $street, $zip, $website])
        {
            $id = self::regionalVenueId($city, 500, $index);
            $address = GDO_Address::blank([
                'address_id' => (string)$id,
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip,
                'address_city' => $city,
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)$id,
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Club- und Tanzlocation. Der Live-Chat ist nur direkt am Standort verfügbar.',
                'room_color' => '#6F42D7',
                'room_category' => '11',
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                'room_view' => '60.0',
                'room_radius' => '0.075',
                'room_www' => $website,
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /**
     * Current mixed venues for the regional catalogue: seven shisha/lounge
     * venues plus three cultural meeting places. Sources were checked against
     * active operator or municipal pages in August 2026.
     */
    private static function createRegionalMixedExpansion(): void
    {
        $owner = GDO_User::getByName('shqiprim');
        $image = self::$ICONS[4];
        $places = [
            ['ZØES Shisha Bar & Club', 3, 'Braunschweig', 52.2668446, 10.5193779, 'Lange Straße 64', '38100', 'https://zoes-bs.de/'],
            ['SAFE Lounge & Bar', 3, 'Braunschweig', 52.2697739, 10.5238469, 'Wendenstraße 49-50', '38100', 'https://safe-bs.de/'],
            ['SixtyFive Lounge', 3, 'Hannover', 52.3892924, 9.7349712, 'Vahrenwalder Straße 42', '30165', 'https://sixtyfivelounge.de/'],
            ['Barbados Shishabar', 3, 'Hannover', 52.3748900, 9.7312020, 'Goethestraße 11', '30169', 'https://barbados-hannover.de/'],
            ['Nova Shisha Bar, Restaurant & Café', 3, 'Hannover', 52.3760345, 9.7333762, 'Georgstraße 7', '30159', 'https://novahannover.de/'],
            ['Relax Café', 3, 'Hannover', 52.3769726, 9.7280167, 'Lange Laube 24', '30159', 'https://relaxhannover.de/'],
            ['AURA Shishabar', 3, 'Hannover', 52.3777025, 9.7296681, 'Otto-Brenner-Straße 9', '30159', 'https://aura-hannover.de/'],
            ['Roof Garden', 3, 'Hannover', 52.3760991, 9.7362421, 'Mehlstraße 2', '30159', 'https://roofgarden-hannover.de/'],
            ['Soziokulturelles Zentrum Braunschweig', 12, 'Braunschweig', 52.2531498, 10.4992802, 'Westbahnhof 13', '38118', 'https://www.soziokultur-bs.de/'],
            ['phaeno Wolfsburg', 12, 'Wolfsburg', 52.4288516, 10.7904410, 'Willy-Brandt-Platz 1', '38440', 'https://www.phaeno.de/'],
        ];

        foreach ($places as $index => [$name, $category, $city, $lat, $lng, $street, $zip, $website])
        {
            $id = self::regionalVenueId($city, 600, $index);
            $address = GDO_Address::blank([
                'address_id' => (string)$id,
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip,
                'address_city' => $city,
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)$id,
                'room_owner' => $owner->getID(),
                'room_name' => $name,
                'room_info' => 'Geprüfte regionale Testlokalität. Der Live-Chat ist nur direkt am Standort verfügbar.',
                'room_color' => '#6F42D7',
                'room_category' => (string)$category,
                'room_pos_lat' => (string)$lat,
                'room_pos_lng' => (string)$lng,
                'room_view' => '60.0',
                'room_radius' => '0.075',
                'room_www' => $website,
                'room_address' => $address->getID(),
                'room_icon' => $image->getID(),
                'room_image' => $image->getID(),
                'room_show_distance' => '1',
            ])->softReplace();
        }
    }

    /** Keep fixed seed areas distinct from user-created rooms. */
    private static function regionalVenueId(string $city, int $series, int $index): int
    {
        return match ($city) {
            'Peine' => 1000 + $series + $index,
            'Braunschweig' => 2000 + $series + $index,
            'Wolfsburg' => 3000 + $series + $index,
            default => 4000 + $series + $index,
        };
    }

    /** Reserve the dynamic ID range for rooms created by users in the app. */
    private static function reserveUserRoomIds(): void
    {
        if (Database::instance()->getLink() instanceof \mysqli)
        {
            Database::instance()->queryWrite('ALTER TABLE lup_room AUTO_INCREMENT = 100000');
        }
    }

}
