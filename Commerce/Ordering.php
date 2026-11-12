<?php
namespace GDO\LinkUUp\Commerce;

/** Provider-independent ordering rules. This preview never moves money. */
final class Ordering
{
    public static function cents(string $amount): int
    {
        if (!preg_match('/^(0|[1-9][0-9]{0,4})(?:[.,]([0-9]{1,2}))?$/D', trim($amount), $m)) throw new \InvalidArgumentException('Ungültiger Eurobetrag');
        return (int)$m[1] * 100 + (int)str_pad($m[2] ?? '', 2, '0');
    }

    public static function menu(array $rows): array
    {
        if (!$rows || count($rows) > 100) throw new \InvalidArgumentException('1 bis 100 Artikel erforderlich');
        $out = []; $ids = [];
        foreach ($rows as $r) {
            foreach (['name', 'category', 'description', 'allergens'] as $key) {
                if (!isset($r[$key]) || !is_string($r[$key]) || strlen($r[$key]) > 600) throw new \InvalidArgumentException('Ungültiges Artikelfeld');
            }
            $id = $r['id'] ?? '';
            if (!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{1,40}$/D', $id) || isset($ids[$id])) throw new \InvalidArgumentException('Artikel-ID fehlt oder ist doppelt');
            if (!trim($r['name']) || !trim($r['category']) || !is_bool($r['available'] ?? null)) throw new \InvalidArgumentException('Name, Kategorie und Verfügbarkeit prüfen');
            $cents = self::cents((string)($r['price'] ?? ''));
            if ($cents < 1) throw new \InvalidArgumentException('Preis muss größer als null sein');
            $ids[$id] = true;
            $out[] = ['id'=>$id,'name'=>trim($r['name']),'category'=>trim($r['category']),'description'=>trim($r['description']),'allergens'=>trim($r['allergens']),'priceCents'=>$cents,'available'=>$r['available']];
        }
        return $out;
    }

    public static function place(array &$state, string $guest, array $request): array
    {
        $key = $request['key'] ?? '';
        if (!is_string($key) || !preg_match('/^[a-zA-Z0-9-]{12,80}$/D', $key)) throw new \InvalidArgumentException('Bestellschlüssel fehlt');
        $lines = $request['lines'] ?? [];
        $table = $request['table'] ?? '';
        if (!is_string($table) || !preg_match('/^[1-9][0-9]?$/D', $table)) throw new \InvalidArgumentException('Tisch 1 bis 99 wählen');
        if (!is_array($lines) || !$lines || count($lines)>30) throw new \InvalidArgumentException('Warenkorb ist leer oder zu groß');
        $fingerprint = hash('sha256', json_encode([$table,$lines]));
        foreach ($state['orders'] as $order) {
            if ($order['venue']===$state['venue']['id'] && $order['guest']===$guest && $order['key']===$key) {
                if ($order['fingerprint']!==$fingerprint) throw new \RuntimeException('Bestellschlüssel bereits anders verwendet');
                return $order;
            }
        }
        if (!($state['orderingEnabled'] ?? false)) throw new \RuntimeException('Bestellungen sind derzeit pausiert');
        if ((int)$table > ($state['tableCount'] ?? 0)) throw new \RuntimeException('Dieser Tisch ist nicht aktiv');
        $catalog = array_column($state['menu'], null, 'id'); $items=[]; $total=0; $seen=[];
        foreach ($lines as $line) {
            $id=$line['id']??''; $qty=$line['qty']??0;
            if (!is_string($id) || isset($seen[$id]) || !isset($catalog[$id]) || !$catalog[$id]['available'] || !is_int($qty) || $qty<1 || $qty>20) throw new \InvalidArgumentException('Artikel oder Menge nicht verfügbar');
            $seen[$id]=true; $item=$catalog[$id];
            // Client-provided prices are never used. Store immutable price/name snapshots.
            $items[]=['id'=>$id,'name'=>$item['name'],'unitCents'=>$item['priceCents'],'qty'=>$qty];
            $total += $item['priceCents']*$qty;
        }
        if ($total>100000) throw new \InvalidArgumentException('Maximal 1.000 Euro pro Testbestellung');
        if (($request['expectedTotal']??null)!==$total) throw new \RuntimeException('Preise haben sich geändert. Warenkorb neu prüfen.');
        $order=['id'=>bin2hex(random_bytes(6)),'key'=>$key,'fingerprint'=>$fingerprint,'guest'=>$guest,'venue'=>$state['venue']['id'],'table'=>$table,'items'=>$items,'totalCents'=>$total,'status'=>'new','payment'=>'simulated_reserved','created'=>gmdate('c'),'messages'=>[],'history'=>[['from'=>null,'to'=>'new','actor'=>$guest,'at'=>gmdate('c')]]];
        $state['orders'][]=$order; return $order;
    }

    public static function operator(array $state, string $user): void
    {
        if ($state['venue']['owner']!==$user) throw new \RuntimeException('Keine Berechtigung für diese Location');
    }

    public static function publication(array &$state, string $user, bool $enabled): void
    {
        self::operator($state, $user);
        if ($enabled && (!(($state['menuReady'] ?? false) && ($state['tablesReady'] ?? false)) || ($state['tableCount'] ?? 0) < 1 || !array_filter($state['menu'], fn($i) => $i['available']))) throw new \RuntimeException('Erst Karte und Tische einrichten');
        $state['orderingEnabled'] = $enabled;
        // Publishing the menu and temporarily pausing new orders are separate.
        if ($enabled) $state['menuPublished'] = true;
    }

    /** Menu visibility does not require tables and never starts ordering. */
    public static function menuPublication(array &$state, string $user, bool $enabled): void
    {
        self::operator($state, $user);
        if ($enabled && (!(($state['menuReady'] ?? false)) || empty($state['menu'])))
            throw new \RuntimeException('Erst eine Karte speichern');
        $state['menuPublished'] = $enabled;
        if (!$enabled) $state['orderingEnabled'] = false;
    }

    public static function tables(array &$state, string $user, int $count): void
    {
        self::operator($state, $user);
        if ($count < 1 || $count > 99) throw new \InvalidArgumentException('1 bis 99 aktive Tische wählen');
        foreach ($state['orders'] as $order) {
            if ($order['venue'] === $state['venue']['id'] && (int)$order['table'] > $count && in_array($order['status'], ['new','accepted','preparing'], true)) throw new \RuntimeException('Ein betroffener Tisch hat noch offene Bestellungen');
        }
        // Stable numbers remain in historical orders; reducing count only deactivates.
        $state['tableCount'] = $count;
        $state['tablesReady'] = true;
    }

    public static function transition(array &$state, string $user, string $id, string $target): array
    {
        self::operator($state,$user);
        foreach ($state['orders'] as &$order) {
            if ($order['id']!==$id || $order['venue']!==$state['venue']['id']) continue;
            if ($order['status']===$target) return $order;
            $allowed=['new'=>['accepted','cancelled'],'accepted'=>['preparing','cancelled'],'preparing'=>['delivered','cancelled'],'delivered'=>['refunded']];
            if (!in_array($target,$allowed[$order['status']]??[],true)) throw new \RuntimeException('Unzulässiger Statuswechsel');
            $order['history'][]=['from'=>$order['status'],'to'=>$target,'actor'=>$user,'at'=>gmdate('c')];
            $order['status']=$target;
            if ($target==='delivered') $order['payment']='simulated_captured';
            if ($target==='cancelled') $order['payment']='simulated_released';
            if ($target==='refunded') $order['payment']='simulated_refunded';
            return $order;
        }
        throw new \RuntimeException('Bestellung nicht gefunden');
    }

    public static function message(array &$state, string $user, string $id, string $text): void
    {
        if (!trim($text) || strlen($text)>1000) throw new \InvalidArgumentException('Nachricht: 1 bis 1000 Zeichen');
        foreach($state['orders'] as &$o) {
            if($o['id']!==$id || $o['venue']!==$state['venue']['id']) continue;
            if($user!==$o['guest'] && $user!==$state['venue']['owner']) throw new \RuntimeException('Keine Berechtigung');
            if(count($o['messages'])>=50) throw new \RuntimeException('Nachrichtenlimit erreicht');
            $o['messages'][]=['role'=>$user===$o['guest']?'guest':'operator','text'=>trim($text),'at'=>gmdate('c')]; return;
        }
        throw new \RuntimeException('Bestellung nicht gefunden');
    }
}
