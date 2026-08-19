<?php
declare(strict_types=1);
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_Char;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Date\GDT_Date;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/** A single, mutually confirmed Cuddle encounter. */
final class LUP_Cuddle extends GDO
{
    public function gdoColumns(): array
    {
        return [
            GDT_AutoInc::make('cuddle_id'),
            // Enforces pair + UTC day exactly once at database level.
            GDT_Char::make('cuddle_key')->ascii()->length(64)->notNull()->unique(),
            GDT_User::make('cuddle_a')->notNull(),
            GDT_User::make('cuddle_b')->notNull(),
            GDT_Date::make('cuddle_day')->notNull(),
            GDT_CreatedBy::make('cuddle_creator'),
            GDT_CreatedAt::make('cuddle_created'),
        ];
    }

    public static function utcDay(): string
    {
        return gmdate('Y-m-d');
    }

    public static function key(GDO_User $a, GDO_User $b, string $day): string
    {
        $ids = [(int)$a->getID(), (int)$b->getID()];
        sort($ids, SORT_NUMERIC);
        return hash('sha256', implode('|', ['lup-cuddle-v2', $ids[0], $ids[1], $day]));
    }

    public static function exists(GDO_User $a, GDO_User $b, ?string $day = null): bool
    {
        $key = self::key($a, $b, $day ?: self::utcDay());
        return self::getByVars(['cuddle_key' => $key]) !== null;
    }

    public static function create(GDO_User $a, GDO_User $b): self
    {
        $day = self::utcDay();
        $ids = [(int)$a->getID(), (int)$b->getID()];
        sort($ids, SORT_NUMERIC);
        return self::blank([
            'cuddle_key' => self::key($a, $b, $day),
            'cuddle_a' => (string)$ids[0],
            'cuddle_b' => (string)$ids[1],
            'cuddle_day' => $day,
        ])->insert();
    }
}
