<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Address\GDO_Address;
use GDO\Core\Module_Core;
use GDO\CORS\Module_CORS;
use GDO\Crypto\BCrypt;
use GDO\CSS\Module_CSS;
use GDO\Date\Time;
use GDO\File\GDO_File;
use GDO\File\ImageResize;
use GDO\File\Method\CronjobImageVariants;
use GDO\Javascript\Module_Javascript;
use GDO\Language\Module_Language;
use GDO\Login\Module_Login;
use GDO\Maps\Module_Maps;
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
        $gizmore->saveSettingVar('User', 'gender', 'male');
        $gizmore->saveSettingVar('Country', 'country_of_origin', 'DE');
        $gizmore->saveSettingVar('Country', 'country_of_living', 'DE');
        GDO_UserPermission::grant($gizmore, 'admin');
        GDO_UserPermission::grant($gizmore, 'staff');

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
        $mira->saveSettingVar('Country', 'country_of_origin', 'US');
        $mira->saveSettingVar('Country', 'country_of_living', 'US');
        GDO_UserPermission::grant($mira, 'admin');
        GDO_UserPermission::grant($mira, 'staff');

        # Settings
		Module_Core::instance()->saveConfigVar('allow_guests', '1');
		# Let every ACL-capable user setting retain its own relation visibility.
		# LinkUUp profiles can therefore make a field stricter than the profile default.
		Module_User::instance()->saveConfigVar('acl_relations', '1');
		// UI order is intentionally separate from module priority/load order.
		Module_User::instance()->saveVar('module_sort', '10');
		$module->saveVar('module_sort', '20');
		Module_CORS::instance()->saveConfigVar('cors_allow_any', '1');
		Module_Language::instance()->saveConfigVar('languages', '["en","de","it","fr","es"]');
		Module_Maps::instance()->saveConfigVar('maps_record_history', '60s');
        Module_Websocket::instance()->saveConfigVar('ws_processor', GDO_PATH . 'GDO/LinkUUp/LUP_Websocket.php');
        Module_Websocket::instance()->saveConfigVar('ws_timer', '100ms');
        Module_Register::instance()->saveConfigVar('captcha', '0');
        Module_Register::instance()->saveConfigVar('email_activation', '0');
        if (GDO_ENV === 'dev')
        {
            Module_LinkUUp::instance()->saveConfigVar('lup_app_url', 'app.lup.localhost');
        }
        if (GDO_ENV === 'pro')
        {
            Module_Core::instance()->saveConfigVar('module_assets', '0');
            Module_CSS::instance()->saveConfigVar('minify_css', '1');
            Module_Javascript::instance()->saveConfigVar('minify_js', 'concat');
            Module_Javascript::instance()->saveConfigVar('compress_js', '1');
        }

		# Perms
		GDO_Permission::create('lup_owner');
		GDO_Permission::create('lup_worker');

		# Image
		$icons = self::installIcons();
		# Category
		$cats = self::installCats($icons);
        self::createCountries();
        self::createPeine();
		self::createWolfsburg();
        self::createBrunswick();
        self::createBraunschweigTestBars();
        self::createBraunschweigEducation();
        self::createBraunschweigRefinedLocations();

		self::createDefaultImageVariants($module);
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

    private static function createPeine(): void
    {
        $gizmore = GDO_User::getByName('gizmore');

        # Address
        $garage = GDO_Address::blank([
            'address_id' => '2',
            'address_company' => null,
            'address_vat' => null,
            'address_name' => 'Garage Peine',
            'address_street' => 'Pulverturmval 68',
            'address_zip' => '31224',
            'address_city' => 'Peine',
            'address_country' => 'DE',
            'address_phone' => '+49 5171 725 29',
            'address_phone_fax' => null,
            'address_phone_mobile' => null,
            'address_email' => 'garage-peine@gmx.de',
        ])->softReplace();

        # Room
        LUP_Room::blank([
            'room_id' => '2',
            'room_owner' => $gizmore->getID(),
            'room_name' => 'Garage',
            'room_info' => 'Die Garage ist der Rock, Metal und Punk Szenetreff in Peine.',
            'room_color' => '#133742', # Gold
            'room_category' => '4',
            'room_pos_lat' => '52.32269098898768',
            'room_pos_lng' => '10.22945615522831',
            'room_view' => '2',
            'room_radius' => '0.2',
            'room_www' => 'https://www.facebook.com/garage.peine/',
            'room_phone' => '05171 79 120 53',
            'room_hours' => "Tu-Su 18:00-03:00;",
            'room_address' => $garage->getID(),
            'room_icon' => self::$ICONS[1]->getID(),
            'room_image' => self::$ICONS[2]->getID(),
            'room_show_distance' => '1',
        ])->softReplace();

        $imagePeine = self::$ICONS[3];
        LUP_Room::blank([
            'room_id' => '3',
            'room_owner' => null,
            'room_name' => 'Peine',
            'room_info' => 'Die Stadt Peine. Kennt man doch,',
            'room_color' => '#133742', # Gold
            'room_category' => '2',
			'room_sort' => '20',
            'room_pos_lat' => '52.32399278721452',
            'room_pos_lng' => '10.2207358761131',
            // Keep city chat access local. Discovery is handled separately by room_view.
            'room_view' => '100',
			// Covers the actual town area; venue rooms retain their precise small radii.
            'room_radius' => '5',
            'room_www' => 'https://www.peine.de/',
            'room_phone' => null,
            'room_hours' => null,
            'room_address' => null,
            'room_icon' => $imagePeine->getId(),
            'room_image' => $imagePeine->getId(),
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
			'room_show_distance' => '0',
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
            'room_show_distance' => '0',
        ])->softReplace();
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
                'address_id' => (string)(20 + $index),
                'address_name' => $name,
                'address_street' => $street,
                'address_zip' => $zip ?: null,
                'address_city' => 'Braunschweig',
                'address_country' => 'DE',
            ])->softReplace();

            LUP_Room::blank([
                'room_id' => (string)(20 + $index),
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
            $id = 42 + $index;
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
            ['Eule XO', 11, 52.2595112, 10.5165467, 'Friedrich-Wilhelm-Straße 39', '38100', 0.075],
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
            $id = 60 + $index;
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

}
