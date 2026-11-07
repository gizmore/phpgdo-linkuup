<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;
use GDO\Core\{GDT,GDT_String,GDT_Text,GDT_Select};
use GDO\Form\{MethodForm,GDT_Form,GDT_AntiCSRF,GDT_Submit};
use GDO\LinkUUp\Commerce\OperatorApplications;
use GDO\LinkUUp\LUP_Room;
use GDO\User\GDO_User;
use GDO\UI\GDT_Paragraph;
final class OperatorApply extends MethodForm
{
    public function getMethodTitle(): string {return 'Betrieb anmelden';}
    protected function createForm(GDT_Form $form): void
    {
        $uid=GDO_User::current()->getID();$choices=[];
        if(ctype_digit((string)$uid))foreach(LUP_Room::table()->allWhere('room_owner='.$uid.' AND room_deleted IS NULL') as $room)$choices[$room->getID()]=$room->gdoVar('room_name');
        $form->addField(GDT_Paragraph::make()->textRaw('Für Gastronomie, Kultur, Bildung und weitere Einrichtungen. Das Team prüft Ihren Antrag und klärt den Vertrag persönlich. Der Antrag allein schaltet keine Betreiberrechte frei.'));
        $form->addField(GDT_Select::make('room')->choices($choices)->emptyLabelRaw('Neue Einrichtung – noch keine Location zugeordnet')->labelRaw('Eigene Location (optional)'));
        foreach(['business'=>['Name der Einrichtung',160],'kind'=>['Art der Einrichtung',100],'address'=>['Vollständige Geschäftsanschrift',300],'contact'=>['Verantwortliche Kontaktperson',160],'email'=>['Geschäftliche E-Mail',254],'phone'=>['Telefon (optional)',80]] as $key=>[$label,$max])$form->addField(GDT_String::make($key)->labelRaw($label)->max($max)->notNull($key!=='phone'));
        $form->addField(GDT_Text::make('details')->labelRaw('Ihre Rolle im Betrieb und gewünschte Nutzung')->max(2000)->notNull());
        $form->addField(GDT_AntiCSRF::make());$form->actions()->addField(GDT_Submit::make()->labelRaw('Prüfung beantragen'));
    }
    public function formValidated(GDT_Form $form): GDT
    {
        $in=[];foreach(['business','kind','address','contact','email','phone','details'] as $key)$in[$key]=(string)$form->getFormVar($key);
        $rid=$form->getFormVar('room');
        try{OperatorApplications::submit((string)GDO_User::current()->getID(),(!$rid||$rid==='0')?null:$rid,$in);return \GDO\UI\GDT_Container::make()->addFields(GDT_Paragraph::make()->textRaw('Ihr Antrag wurde gespeichert. Das Team prüft die Angaben; Freischaltung und Vertragsklärung erfolgen manuell.'), \GDO\UI\GDT_Link::make()->textRaw('Zum Antragsstatus')->href(href('LinkUUp','OperatorApply')));}
        catch(\RuntimeException $e){$form->errorRaw(in_array($e->getCode(),[400,403,404,409],true)?$e->getMessage():'Die Antragsprüfung ist momentan nicht verfügbar. Bitte später erneut versuchen.');return $this->renderPage();}
    }
    public function renderPage(): GDT
    {return $this->templatePHP('page/operator_applications.php',['form'=>$this->getForm(),'rows'=>OperatorApplications::applications((string)GDO_User::current()->getID()),'review'=>false]);}
}
