<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Address\GDO_Address;
use GDO\User\GDO_User;

/**
 * Small, idempotent registry for manually verified locations.
 *
 * A registry entry is deliberately separate from Install.php: adding one
 * verified place must never require wiping and reinstalling LinkUUp.
 */
final class LocationRegistry
{
    /**
     * Entries are introduced one by one. `status=draft` is never imported.
     * A record can only be changed after its source, pin and radius were
     * checked again.
     */
    public const LOCATIONS = [
        'mcd-bs-hamburger-262' => [
            'status' => 'approved',
            'room_id' => 220,
            'address_id' => 220,
            'name' => "McDonald's Braunschweig · Hamburger Straße",
            'category' => 14,
            'color' => '#E39B62',
            'city' => 'Braunschweig',
            'street' => 'Hamburger Straße 262',
            'zip' => '38114',
            'lat' => 52.280614,
            'lng' => 10.519591,
            'view_km' => 32.0,
            'chat_radius_km' => 0.125,
            'website' => 'https://www.mcdonalds-braunschweig-harz.de/mcdonalds-braunschweig-hamburger-strasse/',
            'note' => 'Großer Restaurantstandort mit Außenbereich. Live-Chat nur direkt auf dem Gelände.',
            'source_checked' => '2026-08-16',
        ],
        'mcd-bs-hanse-90' => [
            'status' => 'approved',
            'room_id' => 221,
            'address_id' => 221,
            'name' => "McDonald's Braunschweig · Hansestraße",
            'category' => 14,
            'color' => '#E39B62',
            'city' => 'Braunschweig',
            'street' => 'Hansestraße 90',
            'zip' => '38112',
            'lat' => 52.308820,
            'lng' => 10.510245,
            'view_km' => 32.0,
            'chat_radius_km' => 0.100,
            'website' => 'https://www.mcdonalds-braunschweig-harz.de/mcdonalds-braunschweig-hansestrasse/',
            'note' => 'Großer McCafé- und Restaurantstandort mit Terrasse. Live-Chat nur direkt am Standort.',
            'source_checked' => '2026-08-16',
        ],
        'vw-halle-bs-europaplatz-1' => [
            'status' => 'approved',
            'room_id' => 222,
            'address_id' => 222,
            'name' => 'Volkswagen Halle Braunschweig',
            'category' => 12,
            'color' => '#8B6BDB',
            'city' => 'Braunschweig',
            'street' => 'Europaplatz 1',
            'zip' => '38100',
            'lat' => 52.257359,
            'lng' => 10.519072,
            'view_km' => 32.0,
            'chat_radius_km' => 0.150,
            'website' => 'https://www.volkswagenhalle-braunschweig.de/',
            'note' => 'Große Veranstaltungs- und Sporthalle. Live-Chat nur im Hallen- und direkten Vorplatzbereich.',
            'source_checked' => '2026-08-16',
        ],
        'cinemaxx-wob-willy-brandt-4' => [
            'status' => 'approved',
            'room_id' => 223,
            'address_id' => 223,
            'name' => 'CinemaxX Wolfsburg',
            'category' => 12,
            'color' => '#8B6BDB',
            'city' => 'Wolfsburg',
            'street' => 'Willy-Brandt-Platz 4',
            'zip' => '38440',
            'lat' => 52.428608,
            'lng' => 10.787377,
            'view_km' => 32.0,
            'chat_radius_km' => 0.075,
            'website' => 'https://www.cinemaxx.de/kinoprogramm/wolfsburg/jetzt-im-kino',
            'note' => 'Kino am Hauptbahnhof mit acht Sälen. Live-Chat nur im Kino-Gebäude.',
            'source_checked' => '2026-08-16',
        ],
        'designer-outlets-wob-vorburg-1' => [
            'status' => 'approved',
            'room_id' => 224,
            'address_id' => 224,
            'name' => 'Designer Outlets Wolfsburg',
            'category' => 6,
            'color' => '#D77A57',
            'city' => 'Wolfsburg',
            'street' => 'An der Vorburg 1',
            'zip' => '38440',
            'lat' => 52.429008,
            'lng' => 10.794441,
            'view_km' => 32.0,
            'chat_radius_km' => 0.300,
            'website' => 'https://www.designeroutlets-wolfsburg.de/',
            'note' => 'Großes innerstädtisches Outlet-Center. Live-Chat nur auf dem Center-Gelände.',
            'source_checked' => '2026-08-16',
        ],
        'vw-arena-wob-allerwiesen-1' => [
            'status' => 'approved',
            'room_id' => 225,
            'address_id' => 225,
            'name' => 'Volkswagen Arena Wolfsburg',
            'category' => 13,
            'color' => '#4DBB94',
            'city' => 'Wolfsburg',
            'street' => 'In den Allerwiesen 1',
            'zip' => '38446',
            'lat' => 52.432652,
            'lng' => 10.803815,
            'view_km' => 32.0,
            'chat_radius_km' => 0.150,
            'website' => 'https://www.vfl-wolfsburg.de/stadien/volkswagen-arena/stadioninformationen',
            'note' => 'Stadion mit direktem Bezug zu Spiel- und Eventbesuchen. Live-Chat nur im Stadionareal.',
            'source_checked' => '2026-08-16',
        ],
        'eisarena-wob-allerpark-5' => [
            'status' => 'approved',
            'room_id' => 226,
            'address_id' => 226,
            'name' => 'EisArena Wolfsburg',
            'category' => 13,
            'color' => '#4DBB94',
            'city' => 'Wolfsburg',
            'street' => 'Allerpark 5',
            'zip' => '38448',
            'lat' => 52.437460,
            'lng' => 10.814097,
            'view_km' => 32.0,
            'chat_radius_km' => 0.100,
            'website' => 'https://www.wolfsburg.de/eisarena',
            'note' => 'Eissport- und Veranstaltungsarena im Allerpark. Live-Chat nur im Gebäude.',
            'source_checked' => '2026-08-16',
        ],
        'staatstheater-bs-am-theater-1' => [
            'status' => 'approved',
            'room_id' => 227,
            'address_id' => 227,
            'name' => 'Staatstheater Braunschweig · Großes Haus',
            'category' => 12,
            'color' => '#8B6BDB',
            'city' => 'Braunschweig',
            'street' => 'Am Theater 1',
            'zip' => '38100',
            'lat' => 52.265947,
            'lng' => 10.532309,
            'view_km' => 32.0,
            'chat_radius_km' => 0.075,
            'website' => 'https://staatstheater-braunschweig.de/',
            'note' => 'Großes Haus des Staatstheaters. Live-Chat nur im Theatergebäude.',
            'source_checked' => '2026-08-16',
        ],
        'vielharmonie-bs-bankplatz-7' => [
            'status' => 'approved',
            'room_id' => 228,
            'address_id' => 228,
            'name' => 'Vielharmonie · Restaurant & Bar',
            'category' => 3,
            'color' => '#D66B75',
            'city' => 'Braunschweig',
            'street' => 'Bankplatz 7',
            'zip' => '38100',
            'lat' => 52.261394,
            'lng' => 10.517988,
            'view_km' => 32.0,
            'chat_radius_km' => 0.100,
            'website' => 'https://www.vielharmonie-bs.de/',
            'note' => 'Restaurant und Bar mit weitläufigem Außenbereich. Live-Chat nur im Haus- und Terrassenbereich.',
            'source_checked' => '2026-08-16',
        ],
        'barnabys-bs-oelschlaegern-20' => [
            'status' => 'approved',
            'room_id' => 229,
            'address_id' => 229,
            'name' => "Barnaby's Blues Bar",
            'category' => 3,
            'color' => '#D66B75',
            'city' => 'Braunschweig',
            'street' => 'Ölschlägern 20',
            'zip' => '38100',
            'lat' => 52.261882,
            'lng' => 10.529914,
            'view_km' => 32.0,
            'chat_radius_km' => 0.075,
            'website' => 'https://barnabys-bs.de/',
            'note' => 'Blues-Bar in der Innenstadt. Live-Chat nur direkt am Barstandort.',
            'source_checked' => '2026-08-16',
        ],
        'badsha-bs-oelschlaegern-31' => [
            'status' => 'approved',
            'room_id' => 230,
            'address_id' => 230,
            'name' => 'Badsha Bar Braunschweig',
            'category' => 3,
            'color' => '#D66B75',
            'city' => 'Braunschweig',
            'street' => 'Ölschlägern 31–32',
            'zip' => '38100',
            'lat' => 52.261700,
            'lng' => 10.528245,
            'view_km' => 32.0,
            'chat_radius_km' => 0.075,
            'website' => 'https://www.badsha-bar.de/',
            'note' => 'Cocktail-, Café- und Barstandort. Live-Chat nur direkt im Lokal.',
            'source_checked' => '2026-08-16',
        ],
        'lord-helmchen-bs-fallersleber-35' => [
            'status' => 'approved',
            'room_id' => 231,
            'address_id' => 231,
            'name' => 'Lord Helmchen · Lounge & Bar',
            'category' => 3,
            'color' => '#D66B75',
            'city' => 'Braunschweig',
            'street' => 'Fallersleber Straße 35',
            'zip' => '38100',
            'lat' => 52.268938,
            'lng' => 10.529654,
            'view_km' => 32.0,
            'chat_radius_km' => 0.100,
            'website' => 'https://www.lordhelmchen.eu/',
            'note' => 'Lounge, Restaurant und Veranstaltungshaus. Live-Chat nur im Lokal und auf der Terrasse.',
            'source_checked' => '2026-08-16',
        ],
        'dax-bierboerse-bs-kuechenstrasse-1' => [
            'status' => 'approved',
            'room_id' => 232,
            'address_id' => 232,
            'name' => 'DAX Bierbörse Braunschweig',
            'category' => 11,
            'color' => '#C568D6',
            'city' => 'Braunschweig',
            'street' => 'Küchenstraße 1',
            'zip' => '38100',
            'lat' => 52.266343,
            'lng' => 10.521875,
            'view_km' => 32.0,
            'chat_radius_km' => 0.075,
            'website' => 'https://bierboerse-bs.de/',
            'note' => 'Party- und Clubstandort in der Innenstadt. Live-Chat nur im Lokal.',
            'source_checked' => '2026-08-16',
        ],
        'genusstresor-bs-bankplatz-8' => [
            'status' => 'approved',
            'room_id' => 233,
            'address_id' => 233,
            'name' => 'Genusstresor am Bankplatz',
            'category' => 3,
            'color' => '#D66B75',
            'city' => 'Braunschweig',
            'street' => 'Bankplatz 8',
            'zip' => '38100',
            'lat' => 52.261478,
            'lng' => 10.518871,
            'view_km' => 32.0,
            'chat_radius_km' => 0.100,
            'website' => 'https://genusstresor.de/',
            'note' => 'Großes Restaurant- und Barkonzept am Bankplatz. Live-Chat nur im Haus- und direkten Außenbereich.',
            'source_checked' => '2026-08-16',
        ],
        'sultana-bs-breite-18' => [
            'status' => 'approved',
            'room_id' => 234,
            'address_id' => 234,
            'name' => 'Sultana · Das arabische Restaurant',
            'category' => 14,
            'color' => '#E39B62',
            'city' => 'Braunschweig',
            'street' => 'Breite Straße 18',
            'zip' => '38100',
            'lat' => 52.264731,
            'lng' => 10.516212,
            'view_km' => 32.0,
            'chat_radius_km' => 0.075,
            'website' => 'https://www.sultana-braunschweig.de/',
            'note' => 'Arabisches Restaurant in der Innenstadt. Live-Chat nur direkt im Lokal.',
            'source_checked' => '2026-08-16',
        ],
        'ueberland-bs-willy-brandt-18' => [
            'status' => 'approved',
            'room_id' => 235,
            'address_id' => 235,
            'name' => 'ÜBERLAND · Rooftop Bar & Restaurant',
            'category' => 3,
            'color' => '#D66B75',
            'city' => 'Braunschweig',
            'street' => 'Willy-Brandt-Platz 18',
            'zip' => '38102',
            'lat' => 52.255768,
            'lng' => 10.539922,
            'view_km' => 32.0,
            'chat_radius_km' => 0.100,
            'website' => 'https://www.ueberland-bs.de/',
            'note' => 'Sky-Restaurant mit Rooftop-Bar und Terrasse im BraWoPark. Live-Chat nur im Gebäude.',
            'source_checked' => '2026-08-16',
        ],
    ];

    public static function draft(string $key): array
    {
        if (!isset(self::LOCATIONS[$key]))
        {
            throw new \InvalidArgumentException("Unknown verified location key: {$key}");
        }
        return self::LOCATIONS[$key];
    }

    /** Validate an entry without changing the database. */
    public static function validate(string $key): array
    {
        $entry = self::draft($key);
        $required = ['room_id', 'address_id', 'name', 'category', 'city', 'street', 'zip', 'lat', 'lng', 'view_km', 'chat_radius_km'];
        foreach ($required as $field)
        {
            if (!array_key_exists($field, $entry) || $entry[$field] === '' || $entry[$field] === null)
            {
                throw new \InvalidArgumentException("Missing location field: {$field}");
            }
        }
        if ($entry['lat'] < 47.0 || $entry['lat'] > 56.0 || $entry['lng'] < 5.0 || $entry['lng'] > 16.0)
        {
            throw new \InvalidArgumentException('Location pin is outside Germany.');
        }
        if ($entry['chat_radius_km'] < 0.050 || $entry['chat_radius_km'] > 0.500)
        {
            throw new \InvalidArgumentException('Chat radius must be between 50 and 500 metres.');
        }
        return $entry;
    }

    /** Import every approved registry record during a fresh LinkUUp install. */
    public static function importApproved(): void
    {
        foreach (self::LOCATIONS as $key => $entry)
        {
            if (($entry['status'] ?? null) === 'approved')
            {
                self::import($key);
            }
        }
    }

    /**
     * Explicitly import one approved record. Drafts are blocked by design.
     * The stable IDs make the import safe to repeat without duplicate rooms.
     */
    public static function import(string $key): LUP_Room
    {
        $entry = self::validate($key);
        if ($entry['status'] !== 'approved')
        {
            throw new \LogicException("Location {$key} is still a draft and cannot be imported.");
        }

        $owner = GDO_User::getByName('shqiprim');
        $address = GDO_Address::blank([
            'address_id' => (string)$entry['address_id'],
            'address_name' => $entry['name'],
            'address_street' => $entry['street'],
            'address_zip' => $entry['zip'],
            'address_city' => $entry['city'],
            'address_country' => 'DE',
        ])->softReplace();

        return LUP_Room::blank([
            'room_id' => (string)$entry['room_id'],
            'room_owner' => $owner->getID(),
            'room_name' => $entry['name'],
            'room_info' => $entry['note'],
            'room_color' => $entry['color'],
            'room_category' => (string)$entry['category'],
            'room_pos_lat' => (string)$entry['lat'],
            'room_pos_lng' => (string)$entry['lng'],
            'room_view' => (string)$entry['view_km'],
            'room_radius' => (string)$entry['chat_radius_km'],
            'room_www' => $entry['website'],
            'room_address' => $address->getID(),
            'room_show_distance' => '1',
        ])->softReplace();
    }
}
