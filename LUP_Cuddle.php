<?php
namespace GDO\LinkUUp;

use GDO\Core\GDO;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_Object;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

class LUP_Cuddle extends GDO
{
    public function gdoColumns(): array
    {
        return [
            GDT_AutoInc::make('cuddle_id'),
            GDT_User::make('cuddle_a')->notNull(),
            GDT_User::make('cuddle_b')->notNull(),
            GDT_Object::make('cuddle_room')->table(LUP_Room::table()),
            GDT_CreatedBy::make('cuddle_creator'),
            GDT_CreatedAt::make('cuddle_created'),
        ];
    }

    public static function getCuddle(GDO_User $a, GDO_User $b, bool $getB=false, string $date=null): ?LUP_Cuddle
    {
        $aa = self::swap($a, $b, false);
        $bb = self::swap($a, $b, true);
        $date = new \DateTimeImmutable($date ?: null);
        $dayStart = $date->setTime(0, 0, 0)->format('Y-m-d H:i:s.u');
        $dayEnd   = $date->modify('+1 day')->setTime(0, 0, 0)->format('Y-m-d H:i:s.u');
        $query = self::table()
            ->select('*')
            ->where(sprintf('cuddle_a=%d AND cuddle_b=%d AND cuddle_created >= %s AND cuddle_created < %s',
                $aa->getID(),
                $bb->getID(),
                GDO::quoteS($dayStart),
                GDO::quoteS($dayEnd),
            ));
        return $query->exec()->fetchObject();
    }

    public static function swap(GDO_User $a, GDO_User $b, bool $getB): GDO_User
    {
        $aa = $a->getID() < $b->getID() ? $a : $b;
        $bb = $b->getID() < $a->getID() ? $b : $a;
        return $getB ? $bb : $aa;
    }

    public static function cuddle(GDO_User $a, GDO_User $b, ?LUP_Room $room = null): bool
    {
        $aa = self::swap($a, $b, false);
        $bb = self::swap($a, $b, true);
        self::blank([
            'cuddle_a' => $aa->getID(),
            'cuddle_b' => $bb->getID(),
            'cuddle_'
        ]);
    }

}