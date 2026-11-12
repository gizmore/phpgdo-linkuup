<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Commerce;

/** Admin review is deliberately narrower than operating somebody else's venue. */
final class MenuModeration
{
    public static function check(bool $admin, bool $approved, bool $inactive, string $action): void
    {
        if (!$admin || !$approved) throw new \RuntimeException('Keine Kartenprüfberechtigung für diese Location',403);
        if ($inactive) throw new \RuntimeException('Location ist nicht verfügbar',409);
        if (!in_array($action,['state','save-menu','mfa-begin','mfa-confirm','mfa-verify','mfa-recover'],true))
            throw new \RuntimeException('Kartenprüfung erlaubt keine Betriebs- oder Guthabenaktionen',403);
    }
    public static function save(array &$state, string $actor, array $rows, string $reason): void
    {
        $reason=trim($reason);
        if (strlen($reason)<5 || strlen($reason)>1000) throw new \InvalidArgumentException('Korrektur bitte mit 5 bis 1000 Zeichen begründen');
        $menu=Ordering::menu($rows);
        $history=$state['menuReviewHistory']??[];
        // Fail explicitly instead of silently deleting audit history.
        if (count($history)>=500) throw new \RuntimeException('Prüfhistorie voll; Archivierung durch das Team erforderlich',503);
        $history[]=['actor'=>$actor,'at'=>gmdate('c'),'reason'=>$reason,'revision'=>$state['revision']+1,
            'before'=>hash('sha256',json_encode($state['menu'],JSON_THROW_ON_ERROR)),
            'after'=>hash('sha256',json_encode($menu,JSON_THROW_ON_ERROR))];
        $state['menu']=$menu;$state['menuReady']=true;$state['menuReviewHistory']=$history;$state['revision']++;
        if (!array_filter($menu,static fn($i)=>$i['available'])) $state['orderingEnabled']=false;
    }
    public static function projection(array $s): array
    {
        return ['venue'=>['id'=>$s['venue']['id'],'name'=>$s['venue']['name']],
            'revision'=>$s['revision'],'menu'=>$s['menu'],'menuPublished'=>$s['menuPublished'],
            'menuReviewHistory'=>$s['menuReviewHistory']??[],'canManage'=>false,'canModerate'=>true];
    }
}
