<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Local pilot ledger only: integer cents, no payment provider or real credits.
 * Caller must persist this ledger and order changes together under one lock.
 */
final class TestWallet
{
    public const MAX_TOPUP_CENTS = 100000;
    public const MAX_TOPUP_BALANCE_CENTS = 1000000;
    public const MAX_RESERVE_CENTS = 100000;

    /** Only the requested user's totals; does not create accounts or return journal data. */
    public static function summary(array &$ledger, string $uid): array
    {
        return self::totals(self::account($ledger, $uid));
    }

    public static function topup(array &$ledger, string $uid, int $cents, string $key): array
    {
        self::identifier($key);
        self::amount($cents, self::MAX_TOPUP_CENTS);
        $a = self::account($ledger, $uid);
        if (array_key_exists($key, $a['topups'])) {
            if ($a['topups'][$key] !== $cents) throw new \RuntimeException('Aufladeschlüssel bereits mit anderem Betrag verwendet');
            return ['state'=>'credited', 'amountCents'=>$cents, 'changed'=>false] + self::totals($a);
        }
        if ($a['availableCents'] + $a['reservedCents'] > self::MAX_TOPUP_BALANCE_CENTS - $cents) {
            throw new \RuntimeException('Maximales Testguthaben für Aufladungen erreicht');
        }
        $a['availableCents'] += $cents;
        $a['topups'][$key] = $cents;
        self::commit($ledger, $uid, $a, 'topup', $key, $cents);
        return ['state'=>'credited', 'amountCents'=>$cents, 'changed'=>true] + self::totals($a);
    }

    /** An identical retry remains a no-op even after capture, release or refund. */
    public static function reserve(array &$ledger, string $uid, string $orderid, int $cents): array
    {
        self::identifier($orderid);
        self::amount($cents, self::MAX_RESERVE_CENTS);
        $a = self::account($ledger, $uid);
        self::assertOrderOwner($ledger, $uid, $orderid);
        if (isset($a['holds'][$orderid])) {
            $hold = $a['holds'][$orderid];
            if ($hold['amountCents'] !== $cents) throw new \RuntimeException('Bestellung bereits mit anderem Betrag reserviert');
            return $hold + ['changed'=>false] + self::totals($a);
        }
        if ($a['availableCents'] < $cents) throw new \RuntimeException('Nicht genügend Testguthaben verfügbar');
        $a['availableCents'] -= $cents;
        $a['reservedCents'] += $cents;
        $hold = ['orderId'=>$orderid, 'amountCents'=>$cents, 'state'=>'reserved'];
        $a['holds'][$orderid] = $hold;
        self::commit($ledger, $uid, $a, 'reserve', $orderid, $cents);
        return $hold + ['changed'=>true] + self::totals($a);
    }

    /** Old orders without a hold are explicitly unfunded and never debit the wallet. */
    public static function settle(array &$ledger, string $uid, string $orderid, string $target): array
    {
        self::identifier($orderid);
        if (!in_array($target, ['captured','released','refunded'], true)) throw new \InvalidArgumentException('Ungültiger Guthabenstatus');
        $a = self::account($ledger, $uid);
        self::assertOrderOwner($ledger, $uid, $orderid);
        if (!isset($a['holds'][$orderid])) return ['orderId'=>$orderid, 'amountCents'=>0, 'state'=>'not_reserved', 'changed'=>false] + self::totals($a);
        $hold = $a['holds'][$orderid];
        if ($hold['state'] === $target) return $hold + ['changed'=>false] + self::totals($a);
        $allowed = ['reserved'=>['captured','released'], 'captured'=>['refunded']];
        if (!in_array($target, $allowed[$hold['state']] ?? [], true)) throw new \RuntimeException('Unzulässiger Guthaben-Statuswechsel');
        $amount = $hold['amountCents'];
        if ($hold['state'] === 'reserved') $a['reservedCents'] -= $amount;
        if ($target === 'released' || $target === 'refunded') {
            if ($a['availableCents'] > PHP_INT_MAX - $amount) throw new \RuntimeException('Testguthaben außerhalb des Wertebereichs');
            $a['availableCents'] += $amount;
        }
        $hold['state'] = $target;
        $a['holds'][$orderid] = $hold;
        self::commit($ledger, $uid, $a, $target, $orderid, $amount);
        return $hold + ['changed'=>true] + self::totals($a);
    }

    private static function identifier(string $value): void
    {
        if (!preg_match('/^[a-zA-Z0-9_:.\-]{1,128}$/D', $value)) throw new \InvalidArgumentException('Ungültiger Testguthaben-Schlüssel');
    }

    private static function amount(int $cents, int $limit): void
    {
        if ($cents < 1 || $cents > $limit) throw new \InvalidArgumentException('Testbetrag außerhalb des erlaubten Bereichs');
    }

    private static function account(array $ledger, string $uid): array
    {
        self::identifier($uid);
        $a = $ledger['accounts'][$uid] ?? ['availableCents'=>0, 'reservedCents'=>0, 'topups'=>[], 'holds'=>[]];
        if (!is_array($a) || !is_int($a['availableCents'] ?? null) || !is_int($a['reservedCents'] ?? null)
            || $a['availableCents'] < 0 || $a['reservedCents'] < 0 || !is_array($a['topups'] ?? null) || !is_array($a['holds'] ?? null)) {
            throw new \RuntimeException('Ungültiger Testguthaben-Speicher');
        }
        $reserved = 0;
        foreach ($a['holds'] as $id=>$hold) {
            if (!is_array($hold) || ($hold['orderId'] ?? null) !== (string)$id || !is_int($hold['amountCents'] ?? null)
                || $hold['amountCents'] < 1 || $hold['amountCents'] > self::MAX_RESERVE_CENTS
                || !in_array($hold['state'] ?? null, ['reserved','captured','released','refunded'], true)) throw new \RuntimeException('Ungültige Testreservierung');
            if ($hold['state'] === 'reserved') {
                if ($reserved > PHP_INT_MAX - $hold['amountCents']) throw new \RuntimeException('Ungültiger Reservierungsbetrag');
                $reserved += $hold['amountCents'];
            }
        }
        if ($reserved !== $a['reservedCents']) throw new \RuntimeException('Reservierungen und Testguthaben widersprechen sich');
        return $a;
    }

    private static function assertOrderOwner(array $ledger, string $uid, string $orderid): void
    {
        foreach ($ledger['accounts'] ?? [] as $id=>$account) {
            if ((string)$id !== $uid && isset($account['holds'][$orderid])) throw new \RuntimeException('Bestellung gehört zu einem anderen Testkonto');
        }
    }

    private static function totals(array $a): array
    {
        return ['availableCents'=>$a['availableCents'], 'reservedCents'=>$a['reservedCents']];
    }

    private static function commit(array &$ledger, string $uid, array $account, string $type, string $reference, int $amount): void
    {
        $seq = $ledger['sequence'] ?? 0;
        if (!is_int($seq) || $seq < 0 || $seq === PHP_INT_MAX || !is_array($ledger['journal'] ?? [])) throw new \RuntimeException('Ungültiges Testjournal');
        // Stage the entire in-memory change: validation errors never partially mutate it.
        $next = $ledger;
        $next['accounts'][$uid] = $account;
        $next['sequence'] = $seq + 1;
        $next['journal'][] = ['sequence'=>$seq+1, 'userId'=>$uid, 'operation'=>$type, 'reference'=>$reference,
            'amountCents'=>$amount, 'availableAfterCents'=>$account['availableCents'], 'reservedAfterCents'=>$account['reservedCents'], 'at'=>gmdate('c')];
        $ledger = $next;
    }
}
