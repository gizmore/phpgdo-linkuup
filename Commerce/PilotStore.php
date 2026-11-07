<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Local file transaction only; does not claim production database guarantees. */
final class PilotStore
{
    public static function run(string $dir, bool $write, callable $operation): array
    {
        if (!is_dir($dir) && !mkdir($dir,0700,true) && !is_dir($dir)) throw new \RuntimeException('Lokaler Speicher nicht verfügbar');
        $path=$dir.'/pilot-v2.json';
        $h=fopen($dir.'/pilot-v2.lock','c+');
        if (!$h) throw new \RuntimeException('Speicher gesperrt');
        try {
            if (!flock($h,LOCK_EX)) throw new \RuntimeException('Speicher gesperrt');
            chmod($dir.'/pilot-v2.lock',0600);
            $raw=is_file($path)?file_get_contents($path):'';
            if ($raw===false) throw new \RuntimeException('Speicher nicht lesbar');
            $store=$raw!==''?json_decode($raw,true,32,JSON_THROW_ON_ERROR):['rooms'=>[],'ledger'=>[]];
            if (!is_array($store) || !is_array($store['rooms']??null) || !is_array($store['ledger']??null)) throw new \RuntimeException('Ungültiger lokaler Speicher');
            $result=$operation($store);
            if ($write) {
                $json=json_encode($store,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
                $tmp=tempnam($dir,'write-');
                if ($tmp===false) throw new \RuntimeException('Speichern fehlgeschlagen');
                try {
                    if (!chmod($tmp,0600) || file_put_contents($tmp,$json)!==strlen($json) || !rename($tmp,$path)) throw new \RuntimeException('Speichern fehlgeschlagen');
                } finally {if (is_file($tmp)) unlink($tmp);}
            }
            return $result;
        } finally {flock($h,LOCK_UN);fclose($h);}
    }
}
