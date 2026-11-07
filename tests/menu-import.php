<?php
require __DIR__.'/../Commerce/Ordering.php';require __DIR__.'/../Commerce/MenuImport.php';
use GDO\LinkUUp\Commerce\MenuImport as M;
$head="id;name;category;description;allergens;price;available\n";
$line="limonade;Limonade;Drinks;\"Zitrone; Minze\";;4,50;1\n";
$n=0;function check($v){global $n;if(!$v)throw new RuntimeException('Import assertion');$n++;}
$r=M::parse("\xEF\xBB\xBF".$head.$line);check($r[0]['priceCents']===450&&$r[0]['description']==='Zitrone; Minze');
foreach(['',str_repeat('x',131073),$head.$line.$line,$head.str_replace(';1',';true',$line),$head."a;bad\n",$head.str_replace('4,50','-1',$line),$head."a;\xFF;x;x;;1;1"] as $invalid){try{M::parse($invalid);check(false);}catch(InvalidArgumentException $e){check(true);}}
check(count(M::parse($head."a;=SUM(1);Drinks;literal;;1;1\n"))===1); // Plain text, rendered escaped, no CSV export.
echo json_encode(['passed'=>$n,'scope'=>'bounded CSV parser and menu validation']),PHP_EOL;
