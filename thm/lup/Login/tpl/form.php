<?php

use GDO\Form\GDT_Form;

/** @var $form GDT_Form * */
$name = $form->name;
?>
<div class="login-container">
    <div class="gdo-form login-form">
        <h2>Anmeldung zum Administrationsbereich</h2>
        <form method="POST">
            <div class="col-xs-12 col-sm-6 gdt-string gdo-required">
                <input type="text" placeholder="Bnutzername" id="gdo-1_login" required="required" min="0" max="255" size="32" name="<?=$name?>[login]" value="">
            </div>
            <div class="col-xs-12 col-sm-6 gdt-password gdo-required">
                <input type="password" placeholder="Passwort" required="required" min="59" max="60" size="32" name="<?=$name?>[password]" value="">
            </div>
            <input type="hidden" value="1" name="<?=$name?>[bind_ip]"/>

            <div class="clear"></div>
            <div class="form-buttons">
                <a class="button-forgot-pw gdt-button" href="index.php?mo=Recovery&amp;me=Form">Passwort Vergessen?</a>
                <input type="submit" class="md-button md-primary md-raised login-button" name="<?=$name?>[submit]" value="Einloggen">
            </div>
			<?php
			#echo $form->getField('xsrf')->renderForm()?>
        </form>
    </div>
</div>
