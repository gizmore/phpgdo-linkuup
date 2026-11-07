<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Fresh client GPS plus the server's venue geometry; not proof against GPS spoofing. */
final class Presence
{
    public const MAX_AGE_MS = 120000;
    public const MAX_FUTURE_MS = 5000;
    public const MAX_ACCURACY_METERS = 100;

    /** @return array{canOrder:bool,orderBlockedReason:?string} */
    public static function status(bool $orderingEnabled, mixed $position, int $nowMs, callable $inside): array
    {
        $blocked = static fn(string $reason): array => ['canOrder' => false, 'orderBlockedReason' => $reason];
        if (!$orderingEnabled) return $blocked('ordering_paused');
        if (!is_array($position)) return $blocked('location_required');
        foreach (['latitude', 'longitude', 'accuracy', 'timestamp'] as $key) {
            if (!isset($position[$key]) || (!is_int($position[$key]) && !is_float($position[$key]))
                || !is_finite((float)$position[$key])) return $blocked('location_invalid');
        }
        $lat = (float)$position['latitude']; $lng = (float)$position['longitude'];
        $accuracy = (float)$position['accuracy']; $stamp = (float)$position['timestamp'];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || $accuracy < 0 || $stamp <= 0) {
            return $blocked('location_invalid');
        }
        if ($stamp < $nowMs - self::MAX_AGE_MS || $stamp > $nowMs + self::MAX_FUTURE_MS) {
            return $blocked('location_stale');
        }
        if ($accuracy > self::MAX_ACCURACY_METERS) return $blocked('location_inaccurate');
        // Accuracy is never added to the venue boundary, nor is a paid distance boost.
        if ($inside($lat, $lng) !== true) return $blocked('outside_location');
        return ['canOrder' => true, 'orderBlockedReason' => null];
    }

    public static function requireOrder(array $status): void
    {
        if (($status['canOrder'] ?? false) === true) return;
        $messages = [
            'ordering_paused' => 'Bestellungen sind derzeit pausiert.',
            'location_required' => 'Bitte prüfe deinen aktuellen Standort, um vor Ort zu bestellen.',
            'location_invalid' => 'Der Standort ist ungültig. Bitte erneut prüfen.',
            'location_stale' => 'Der Standort ist nicht aktuell. Bitte erneut prüfen.',
            'location_inaccurate' => 'Der Standort ist zu ungenau. Bitte erneut prüfen.',
            'outside_location' => 'Bestellen ist nur vor Ort in dieser Location möglich.',
        ];
        throw new \RuntimeException($messages[$status['orderBlockedReason'] ?? ''] ?? 'Bestellen ist derzeit nicht möglich.', 403);
    }
}
