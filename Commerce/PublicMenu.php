<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;
final class PublicMenu
{
    public static function project(string $id,string $name,?array $state,bool $eligible): array
    {
        $visible=$eligible && ($state['menuPublished']??false)===true;
        $menu=[];
        if($visible)foreach($state['menu']??[] as $item)$menu[]=array_intersect_key($item,array_flip(['id','name','category','description','allergens','priceCents','available']));
        return ['venue'=>['id'=>$id,'name'=>$name],'menu'=>$menu,'menuPublished'=>$visible,
            'orderingEnabled'=>$visible&&($state['orderingEnabled']??false)===true,'canManage'=>false,
            'canOrder'=>false,'readOnly'=>true,'orders'=>[],'tableCount'=>0,'localPilot'=>true];
    }
}
