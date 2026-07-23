<div class="login-container">
    <div class="gdo-form login-form">
        <h2 class="gdo-form-title">Passwort zurücksetzen</h2>
        <form method="POST">
            <div class="gdt-container gdt-username col-xs-12 col-sm-5">
                <input placeholder="Benutzername" type="text" id="gdo-1_login" pattern="[-_\p{L}0-9]{2,32}" min="2" max="32" size="32" name="form[login]"
                       value="">
            </div>
            <div class="gdt-container or-container col-xs-12 col-sm-2">oder</div>
            <div class="gdt-container gdt-email col-xs-12 col-sm-5">
                <input placeholder="E-Mail" type="email" name="form[email]" value="">
            </div>
            <div class="clear"></div>
            <div class="form-buttons">
                <a class="button-forgot-pw" href="index.php?mo=Login&amp;me=Form">zurück zum Login</a>
                <input type="submit" class="md-button md-primary md-raised login-button" name="submit" value="Passwort anfordern">
				<?php
				echo $form->getField('xsrf')->renderForm() ?>
            </div>
        </form>
    </div>
</div>
</div>
