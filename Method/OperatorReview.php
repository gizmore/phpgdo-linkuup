<?php
declare(strict_types=1);
namespace GDO\LinkUUp\Method;
use GDO\Core\{GDT,GDT_UInt,GDT_Select,GDT_Text,GDT_Checkbox};
use GDO\Form\{MethodForm,GDT_Form,GDT_AntiCSRF,GDT_Submit};
use GDO\LinkUUp\Commerce\OperatorApplications;
use GDO\User\GDO_User;
final class OperatorReview extends MethodForm
{
    public function getPermission(): ?string {return 'admin';}
    public function getMethodTitle(): string {return 'Betriebsanträge prüfen';}
    protected function createForm(GDT_Form $form): void
    {
        $form->addFields(GDT_UInt::make('application')->labelRaw('Antragsnummer')->notNull(),GDT_UInt::make('version')->labelRaw('Aktuelle Version aus der Übersicht')->notNull(),GDT_Select::make('decision')->choices(['assign'=>'Location zuordnen (ohne Freigabe)','approved'=>'Freischalten','rejected'=>'Ablehnen','revoked'=>'Freigabe widerrufen'])->notNull()->labelRaw('Entscheidung'),GDT_UInt::make('room')->labelRaw('Location-ID (nur für Zuordnung)'),GDT_Text::make('note')->max(2000)->notNull()->labelRaw('Prüfvermerk / Begründung'),GDT_Checkbox::make('confirmed')->initial('0')->labelRaw('Prüfung und Vertragsklärung abgeschlossen'),GDT_AntiCSRF::make());
        $form->actions()->addField(GDT_Submit::make()->labelRaw('Entscheidung speichern'));
    }
    public function formValidated(GDT_Form $form): GDT
    {
        try{if($form->getFormVar('decision')==='assign'){OperatorApplications::assignLocation((string)GDO_User::current()->getID(),(string)$form->getFormVar('application'),(int)$form->getFormVar('version'),(string)$form->getFormVar('room'),(string)$form->getFormVar('note'));}else OperatorApplications::decision((string)GDO_User::current()->getID(),(string)$form->getFormVar('application'),(int)$form->getFormVar('version'),(string)$form->getFormVar('decision'),(string)$form->getFormVar('note'),$form->getFormValue('confirmed')===true);}
        catch(\RuntimeException $e){$form->errorRaw(in_array($e->getCode(),[400,403,404,409],true)?$e->getMessage():'Die Antragsprüfung ist momentan nicht verfügbar. Bitte später erneut versuchen.');}
        return $this->renderPage();
    }
    public function renderPage(): GDT
    {return $this->templatePHP('page/operator_applications.php',['form'=>$this->getForm(),'rows'=>OperatorApplications::applications((string)GDO_User::current()->getID(),true),'review'=>true]);}
}
