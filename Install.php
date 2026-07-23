<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Address\GDO_Address;
use GDO\Core\Module_Core;
use GDO\CORS\Module_CORS;
use GDO\Crypto\BCrypt;
use GDO\File\GDO_File;
use GDO\File\ImageResize;
use GDO\File\Method\CronjobImageVariants;
use GDO\Language\Module_Language;
use GDO\Login\Module_Login;
use GDO\Maps\Module_Maps;
use GDO\Register\Module_Register;
use GDO\User\GDO_Permission;
use GDO\User\GDO_User;
use GDO\User\GDO_UserPermission;
use GDO\User\GDT_UserType;
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
		'1' => ['Länder', 0],
		'2' => ['Städte', null],
		'3' => ['Bars', null],
        '4' => ['Kneipen', null],
        '5' => ['Cafe', null],
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
        $gizmore->saveSettingVar('Login', 'password', BCrypt::create($passwords['gizmore'])->__toString());
        $gizmore->saveSettingVar('User', 'gender', 'male');
        GDO_UserPermission::grant($gizmore, 'admin');
        GDO_UserPermission::grant($gizmore, 'staff');

        # squippi
        $squippi = GDO_User::blank([
            'user_id' => '3',
            'user_type' => GDT_UserType::MEMBER,
            'user_name' => 'squiprim',
            'user_level' => '65535',
        ])->softReplace();
        $squippi->saveSettingVar('Login', 'password', BCrypt::create($passwords['squiprim'])->__toString());
        $squippi->saveSettingVar('User', 'gender', 'male');
        GDO_UserPermission::grant($squippi, 'admin');
        GDO_UserPermission::grant($squippi, 'staff');

        # Settings
		Module_Core::instance()->saveConfigVar('allow_guests', '1');
		Module_CORS::instance()->saveConfigVar('cors_allow_any', '1');
		Module_Language::instance()->saveConfigVar('languages', '["en","de","it","fr","es"]');
		Module_Maps::instance()->saveConfigVar('maps_record_history', '6ßs');
        Module_Websocket::instance()->saveConfigVar('ws_processor', GDO_PATH . 'GDO/LinkUUp/LUP_Websocket.php');
        Module_Websocket::instance()->saveConfigVar('ws_timer', '100ms');
        Module_Register::instance()->saveConfigVar('captcha', '0');
        Module_Register::instance()->saveConfigVar('email_activation', '0');

		# Perms
		GDO_Permission::create('lup_owner');
		GDO_Permission::create('lup_worker');

		# Image
		$icons = self::installIcons();
		$germanyIcon = $icons[0]->getID();

		# Category
		$cats = self::installCats($icons);
		$germany = $cats['1'];

		# Address
		$add = GDO_Address::blank([
			'address_id' => '1',
			'address_company' => 'Deutschland GmbH',
			'address_vat' => '0000001',
			'address_name' => 'Angela Merkel',
			'address_street' => 'Am Brandenburger Tor 1',
			'address_zip' => '10000',
			'address_city' => 'Berlin',
			'address_country' => 'DE',
			'address_phone' => null,
			'address_phone_fax' => null,
			'address_phone_mobile' => null,
			'address_email' => 'support@linkuup.de',
		])->softReplace();
		$germanyAddress = $add;

		# Room
		LUP_Room::blank([
			'room_id' => '1',
			'room_owner' => $gizmore->getID(),
			'room_name' => 'Germany',
			'room_info' => 'Der Deutschland Kanal nur für Euch!',
			'room_color' => '#FFD700', # Gold
			'room_category' => $germany->getID(),
			'room_pos_lat' => '51.1093728415025',
			'room_pos_lng' => '10.398766823981518',
			'room_view' => '42000.0',
			'room_radius' => '800.0',
			'room_www' => 'https://de.wikipedia.org/wiki/Deutschland',
			'room_phone' => '+49 176 - 59 59 88 44',
			'room_hours' => null,
			'room_address' => $germanyAddress->getID(),
			'room_icon' => $germanyIcon,
			'room_image' => $germanyIcon,
			'room_show_distance' => '0',
		])->softReplace();

        self::createPeine();
        self::createBrunswick();

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
            'room_pos_lat' => '52.32399278721452',
            'room_pos_lng' => '10.2207358761131',
            'room_view' => '20',
            'room_radius' => '20',
            'room_www' => 'https://www.peine.de/',
            'room_phone' => null,
            'room_hours' => null,
            'room_address' => null,
            'room_icon' => $imagePeine->getId(),
            'room_image' => $imagePeine->getId(),
            'room_show_distance' => '1',
        ])->softReplace();
    }

    private static function createBrunswick(): void
    {
        $squippi = GDO_User::getByName('squiprim');
        $imageBS = self::$ICONS[4];
        LUP_Room::blank([
            'room_id' => '4',
            'room_owner' => $squippi->getID(),
            'room_name' => 'Braunschweig',
            'room_info' => 'Nur innerhalb der Stadt Braunschweig. Hier kommt LinkUUp her.',
            'room_color' => '#133742', # Gold
            'room_category' => '2',
            'room_pos_lat' => '52.247659326009185',
            'room_pos_lng' => '10.523846179408098',
            'room_view' => '32',
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
}
