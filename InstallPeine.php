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
		self::seedSkatepark($icons);
		self::seedCemetery($icons);
		self::seedGunzelinSchool($icons);
		self::seedCityPark($icons);
		self::seedCityCentre($icons);
		self::seedMarketSquare($icons);
		self::seedStation($icons);
		self::seedSilberkampGymnasium($icons);
		self::seedRestaurants($icons);
	}

	private static function seedGarage(array $icons): void
	{
		$garage = GDO_Address::blank([
			'address_id' => '1000',
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
			'room_id' => '1000',
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
			'address_id' => '1001',
			'address_name' => 'Rathaus & Bürgerbüro Peine',
			'address_street' => 'Kantstraße 5',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 49-0',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1001',
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
			'address_id' => '1002',
			'address_name' => 'LinkUUp Dev-Standort',
			'address_street' => 'Am Bauhof 15',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1002',
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
			'address_id' => '1003',
			'address_name' => 'Standesamt',
			'address_street' => 'Woltorfer Straße 77 B',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1003',
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
			'address_id' => '1004',
			'address_name' => 'Agentur für Arbeit Peine',
			'address_street' => 'Im Schleusenteich 1',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 7740-62',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1004',
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

	/** Public skate and inline park at the Unternehmenspark Peine II. */
	private static function seedSkatepark(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1005',
			'address_name' => 'Skatepark Peine',
			'address_street' => 'Hans-Gallinis-Straße',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1005',
			'room_owner' => null,
			'room_name' => 'Skatepark Peine',
			'room_info' => 'Öffentliche Skate- und Inlineranlage am Unternehmenspark Peine II.',
			'room_color' => '#2D946A',
			'room_category' => '13',
			'room_active' => '1',
			'room_pos_lat' => '52.3217000',
			'room_pos_lng' => '10.2429000',
			'room_view' => '10.0',
			'room_radius' => '0.150',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Evangelischer St.-Jakobi-Friedhof, Gunzelinstraße 31. */
	private static function seedCemetery(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1006',
			'address_name' => 'St.-Jakobi-Friedhof Peine',
			'address_street' => 'Gunzelinstraße 31',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 6116',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1006',
			'room_owner' => null,
			'room_name' => 'Friedhof Peine',
			'room_info' => 'Evangelischer St.-Jakobi-Friedhof an der Gunzelinstraße.',
			'room_color' => '#66706D',
			'room_category' => '8',
			'room_active' => '1',
			'room_pos_lat' => '52.3262639',
			'room_pos_lng' => '10.2391667',
			'room_view' => '10.0',
			'room_radius' => '0.100',
			'room_www' => 'https://www.stjakobi-peine.de/Ueber-uns/Friedhof',
			'room_phone' => '05171 6116',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Gunzelin-Realschule, Gunzelinstraße 42. */
	private static function seedGunzelinSchool(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1007',
			'address_name' => 'Gunzelin-Realschule',
			'address_street' => 'Gunzelinstraße 42',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 7902710',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1007',
			'room_owner' => null,
			'room_name' => 'Gunzelin-Realschule',
			'room_info' => 'Ganztags-Realschule in Peine.',
			'room_color' => '#3D6CC9',
			'room_category' => '16',
			'room_active' => '1',
			'room_pos_lat' => '52.3257000',
			'room_pos_lng' => '10.2412800',
			'room_view' => '10.0',
			'room_radius' => '0.100',
			'room_www' => 'https://rs-peine.de/',
			'room_phone' => '05171 7902710',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Stadtpark Peine, including the pond and minigolf grounds. */
	private static function seedCityPark(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1008',
			'address_name' => 'Stadtpark Peine',
			'address_street' => 'Kantstraße',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1008',
			'room_owner' => null,
			'room_name' => 'Stadtpark Peine',
			'room_info' => 'Stadtpark mit See, Minigolf und Sitzmöglichkeiten.',
			'room_color' => '#2D946A',
			'room_category' => '20',
			'room_active' => '1',
			'room_pos_lat' => '52.3215700',
			'room_pos_lng' => '10.2345260',
			'room_view' => '10.0',
			// Roughly the radius of the 40,000 m² park grounds.
			'room_radius' => '0.125',
			'room_www' => 'https://www.peine.de/de/rathaus/stadtportraet/stadtrundgang/Stadtpark.php',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Peine city centre, centred at St.-Jakobi-Kirche. */
	private static function seedCityCentre(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1009',
			'address_name' => 'St.-Jakobi-Kirche',
			'address_street' => 'Breite Straße 14',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1009',
			'room_owner' => null,
			'room_name' => 'Peiner Innenstadt',
			'room_info' => 'Die Peiner Innenstadt rund um St. Jakobi.',
			'room_color' => '#3D6CC9',
			'room_category' => '2',
			'room_active' => '1',
			'room_pos_lat' => '52.3222900',
			'room_pos_lng' => '10.2273700',
			'room_view' => '10.0',
			'room_radius' => '0.600',
			'room_www' => 'https://www.stjakobi-peine.de/',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Historic market square in the pedestrian zone. */
	private static function seedMarketSquare(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1010',
			'address_name' => 'Marktplatz Peine',
			'address_street' => 'Am Markt',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1010',
			'room_owner' => null,
			'room_name' => 'Peiner Marktplatz',
			'room_info' => 'Historischer Marktplatz in der Peiner Fußgängerzone.',
			'room_color' => '#C9821E',
			'room_category' => '2',
			'room_active' => '1',
			'room_pos_lat' => '52.3233900',
			'room_pos_lng' => '10.2258500',
			'room_view' => '10.0',
			'room_radius' => '0.100',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Deutsche Bahn station at Bahnhofsplatz 1. */
	private static function seedStation(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1011',
			'address_name' => 'Bahnhof Peine',
			'address_street' => 'Bahnhofsplatz 1',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1011',
			'room_owner' => null,
			'room_name' => 'Peine Bahnhof',
			'room_info' => 'Bahnhof Peine mit Regionalzug- und Busanschluss.',
			'room_color' => '#4E6F92',
			'room_category' => '2',
			'room_active' => '1',
			'room_pos_lat' => '52.3191500',
			'room_pos_lng' => '10.2322500',
			'room_view' => '10.0',
			'room_radius' => '0.150',
			'room_www' => 'https://www.bahnhof.de/peine',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/** Gymnasium am Silberkamp, the larger local school campus. */
	private static function seedSilberkampGymnasium(array $icons): void
	{
		$address = GDO_Address::blank([
			'address_id' => '1012',
			'address_name' => 'Gymnasium am Silberkamp',
			'address_street' => 'Am Silberkamp 30',
			'address_zip' => '31224',
			'address_city' => 'Peine',
			'address_country' => 'DE',
			'address_phone' => '+49 5171 4019500',
		])->softReplace();

		$image = $icons[3];
		LUP_Room::blank([
			'room_id' => '1012',
			'room_owner' => null,
			'room_name' => 'Gymnasium am Silberkamp',
			'room_info' => 'Gymnasium am Silberkamp in Peine.',
			'room_color' => '#3D6CC9',
			'room_category' => '16',
			'room_active' => '1',
			'room_pos_lat' => '52.3225930',
			'room_pos_lng' => '10.2392850',
			'room_view' => '10.0',
			'room_radius' => '0.150',
			'room_www' => 'https://www.silberkamp.de/',
			'room_phone' => '05171 4019500',
			'room_address' => $address->getID(),
			'room_icon' => $image->getID(),
			'room_image' => $image->getID(),
			'room_show_distance' => '1',
		])->softReplace();
	}

	/**
	 * Public-map restaurant snapshot for Peine and its directly connected city
	 * districts. Re-running the installer keeps this list idempotent.
	 */
	private static function seedRestaurants(array $icons): void
	{
		$restaurants = [
			['Mephisto', 52.3247604, 10.2307823, 'Hagenstraße 26', '31224'],
			['Zur Hollandsmühle', 52.3066752, 10.2261689, 'Hollandsmühle 4', '31226'],
			['Cafe Couture', 52.3229604, 10.2259672, 'Am Markt 22', '31224'],
			['Restaurant No. 90', 52.3230273, 10.2258566, 'Am Markt 22/23', '31224'],
			['La Bruschetta', 52.3225532, 10.2263507, 'Breite Straße 6', '31224'],
			['Taormina', 52.3242585, 10.2269908, null, '31224'],
			['Sushi Restaurant Viet Küche', 52.3460083, 10.2414566, 'Peiner Straße 15A', '31228'],
			['Gasthaus zur Sonne', 52.3487371, 10.2432568, 'Edemissener Straße 6', '31228'],
			['Hemingway', 52.3230840, 10.2260709, null, '31224'],
			['Center Court', 52.3349421, 10.2211270, null, '31224'],
			['Härke BrauereiAusschank', 52.3207247, 10.2299440, 'Gröpern 5', '31224'],
			["Domino's Pizza", 52.3241712, 10.2294775, 'Hagenmarkt 25', '31224'],
			['Vina', 52.3244842, 10.2312297, 'Senator-Voges-Straße 2', '31224'],
			['bei Artour', 52.3208501, 10.2285030, 'Wallstraße 13', '31224'],
			['Theaterrestaurant Peiner Festsäle', 52.3164306, 10.2323192, 'Friedrich-Ebert-Platz 12', '31226'],
			['Osteria Luce', 52.3490910, 10.2449396, 'Edemissener Straße 12', '31228'],
			['Belgrad Grill', 52.3202048, 10.2425816, 'Woltorfer Straße 70', '31224'],
			['Steak & Grillhaus Argentina', 52.3236670, 10.2287590, 'Hagenstraße 17', '31224'],
			['Xin Hua', 52.3221698, 10.2265737, null, '31224'],
			['Vereinsheim KGV Reitlahe e.V.', 52.3291551, 10.2167244, null, '31224'],
			['Bürger-Jäger-Heim', 52.3197188, 10.2327911, 'Beethovenstraße 6', '31224'],
			['Hotel und Gaststätte Löns Krug Geffers', 52.3151882, 10.2388443, 'Braunschweiger Straße 72', '31226'],
			['Asia Gourmet', 52.3376982, 10.2452099, 'Dieselstraße 8c', '31224'],
			['Restaurant Peking', 52.3264305, 10.2337393, 'Lessingstraße 5', '31224'],
			['Gasthaus Zum Sundern', 52.3415836, 10.2171707, 'Sundern 1', '31228'],
			['Madame Le - Sushi & Panasiatisch', 52.3227907, 10.2261255, 'Breite Straße 3', '31224'],
			['Cyrano', 52.3227804, 10.2335304, 'Kantstraße 12', '31224'],
			['PTG Werksgasthaus', 52.3148052, 10.2413447, 'Gerhard-Lucas-Meyer-Straße 14', '31226'],
		];

		$image = $icons[3];
		foreach ($restaurants as $index => [$name, $lat, $lng, $street, $zip])
		{
			$id = 1013 + $index;
			$address = GDO_Address::blank([
				'address_id' => (string)$id,
				'address_name' => $name,
				'address_street' => $street,
				'address_zip' => $zip,
				'address_city' => 'Peine',
				'address_country' => 'DE',
			])->softReplace();

			LUP_Room::blank([
				'room_id' => (string)$id,
				'room_owner' => null,
				'room_name' => $name,
				'room_info' => 'Restaurant in Peine.',
				'room_color' => '#C9821E',
				'room_category' => '14',
				'room_active' => '1',
				'room_pos_lat' => (string)$lat,
				'room_pos_lng' => (string)$lng,
				'room_view' => '10.0',
				'room_radius' => '0.075',
				'room_address' => $address->getID(),
				'room_icon' => $image->getID(),
				'room_image' => $image->getID(),
				'room_show_distance' => '1',
			])->softReplace();
		}
	}

}
