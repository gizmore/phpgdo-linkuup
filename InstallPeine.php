<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Address\GDO_Address;
use GDO\User\GDO_User;

/**
 * Seed data for Peine.
 *
 * Keep Peine venues here rather than growing the generic installer further.
 * Each later entry needs a stable room/address ID, a checked pin, a category,
 * and a deliberately small chat radius.
 */
final class InstallPeine
{
	/** @param array<int, object> $icons Installed LinkUUp image files. */
	public static function seed(array $icons): void
	{
		self::seedGarage($icons);
		self::seedCityChat($icons);
		self::seedTownHall($icons);
		self::seedMogwai($icons);
		self::seedStandesamt($icons);
		self::seedEmploymentAgency($icons);
	}

	private static function seedGarage(array $icons): void
	{
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

		LUP_Room::blank([
			'room_id' => '2',
			'room_owner' => GDO_User::getByName('gizmore')->getID(),
			'room_name' => 'Garage',
			'room_info' => 'Die Garage ist der Rock, Metal und Punk Szenetreff in Peine.',
			'room_color' => '#133742',
			'room_category' => '4',
			'room_pos_lat' => '52.32269098898768',
			'room_pos_lng' => '10.22945615522831',
			'room_view' => '2',
			'room_radius' => '0.2',
			'room_www' => 'https://www.facebook.com/garage.peine/',
			'room_phone' => '05171 79 120 53',
			'room_hours' => 'Tu-Su 18:00-03:00;',
			'room_address' => $garage->getID(),
			'room_icon' => $icons[1]->getID(),
			'room_image' => $icons[2]->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	private static function seedCityChat(array $icons): void
	{
		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '3',
			'room_owner' => null,
			'room_name' => 'Peine',
			'room_info' => 'Die Stadt Peine. Kennt man doch.',
			'room_color' => '#133742',
			'room_category' => '2',
			'room_sort' => '20',
			'room_pos_lat' => '52.32399278721452',
			'room_pos_lng' => '10.2207358761131',
			// Discoverable regionally; participation remains local to Peine.
			'room_view' => '100',
			'room_radius' => '5',
			'room_www' => 'https://www.peine.de/',
			'room_phone' => null,
			'room_hours' => null,
			'room_address' => null,
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Rathaus, Bürgerbüro share this single physical location. */
	private static function seedTownHall(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '236',
			'address_name' => 'Rathaus & Bürgerbüro Peine',
			'address_street' => 'Kantstraße 5',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 49-0',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '236',
			'room_owner' => null,
			'room_name' => 'Rathaus & Bürgerbüro',
			'room_info' => 'Stadtverwaltung Peine mit Bürgerbüro.',
			'room_color' => '#3D6CC9',
			'room_category' => '16',
			'room_pos_lat' => '52.321721',
			'room_pos_lng' => '10.233262',
			'room_view' => '32.0',
			'room_radius' => '0.075',
			'room_www' => 'https://www.peine.de/de/rathaus/stadtverwaltung.php',
			'room_phone' => '05171 49-0',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Development pin for local GPS and room-flow tests. */
	private static function seedMogwai(array $icons): void
	{
		$owner = GDO_User::getByName('gizmore');
		$address = GDO_Address::blank([
			'address_id' => '237',
			'address_name' => 'LinkUUp Dev-Standort',
			'address_street' => 'Am Bauhof 15',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '237',
			'room_owner' => $owner->getID(),
			'room_name' => 'Mogwai',
			'room_info' => 'Wohnsitz eines LinkUUp-Programmierers.',
			'room_color' => '#4DBB94',
			'room_category' => '6',
			'room_active' => '1',
			'room_pos_lat' => '52.3238495',
			'room_pos_lng' => '10.2439493',
			'room_view' => '2.0',
			'room_radius' => '0.050',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Local LinkUUp test point near the Woltorfer Straße business area. */
	private static function seedStandesamt(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '238',
			'address_name' => 'Standesamt',
			'address_street' => 'Woltorfer Straße 77 B',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '238',
			'room_owner' => null,
			'room_name' => 'Standesamt',
			'room_info' => 'LinkUUp-Testort an der Woltorfer Straße.',
			'room_color' => '#3D6CC9',
			'room_category' => '6',
			'room_active' => '1',
			'room_pos_lat' => '52.3207751',
			'room_pos_lng' => '10.2467682',
			'room_view' => '32.0',
			'room_radius' => '0.075',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Agentur für Arbeit Peine, Im Schleusenteich 1. */
	private static function seedEmploymentAgency(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '239',
			'address_name' => 'Agentur für Arbeit Peine',
			'address_street' => 'Im Schleusenteich 1',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 7740-62',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '239',
			'room_owner' => null,
			'room_name' => 'Arbeitsamt Peine',
			'room_info' => 'Agentur für Arbeit Peine.',
			'room_color' => '#3D6CC9',
			'room_category' => '16',
			'room_active' => '1',
			'room_pos_lat' => '52.3210250',
			'room_pos_lng' => '10.2389154',
			'room_view' => '10.0',
			'room_radius' => '0.177',
			'room_www' => 'https://www.arbeitsagentur.de/vor-ort/hildesheim/peine',
			'room_phone' => '05171 7740-62',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

}
