<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Bounded UTF-8 CSV input. Never evaluates formulas or writes uploaded files. */
final class MenuImport
{
    public static function parse(string $csv): array
    {
        if(strlen($csv)>131072 || !preg_match('//u',$csv) || str_contains($csv,"\0"))throw new \InvalidArgumentException('CSV muss UTF-8 sein und darf höchstens 128 KB enthalten');
        $csv=preg_replace('/^\xEF\xBB\xBF/','',$csv);
        $stream=fopen('php://temp','r+');
        try {
            fwrite($stream,$csv);rewind($stream);
            $header=fgetcsv($stream,0,';','"','');
            if($header!==['id','name','category','description','allergens','price','available'])throw new \InvalidArgumentException('CSV-Kopf: id;name;category;description;allergens;price;available');
            $rows=[];
            while(($cells=fgetcsv($stream,0,';','"',''))!==false){
                if($cells===[null])continue;
                if(count($cells)!==7 || count($rows)>=100)throw new \InvalidArgumentException('CSV: höchstens 100 vollständige Artikel');
                if(!in_array($cells[6],['0','1'],true))throw new \InvalidArgumentException('Verfügbarkeit muss 0 oder 1 sein');
                $row=array_combine($header,$cells);$row['available']=$cells[6]==='1';$rows[]=$row;
            }
            return Ordering::menu($rows);
        } finally {fclose($stream);}
    }
}
