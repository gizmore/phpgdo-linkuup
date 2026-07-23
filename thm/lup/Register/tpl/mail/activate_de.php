<?php
/** @var string $username */
/** @var string $activation_url */
?>
<p><h1>Willkommen bei LinkUup</h1></p>
<p>Hallo <?=$username?>,</p>
<p>um deine Registrierung abzuschließen, klicke auf den folgenden Button.</p>
<p style="text-align:center;">
    <br/>
    <a
            style="border-radius: 5px; padding: 15px 30px; background-image: linear-gradient(to top, #b712ff 0%, #9c1cd1 100%); text-decoration: none; color:#fff; font-size:18px;"
            href="<?php
			echo $activation_url; ?>">Aktivieren</a>
    <br/>
    <br/>
</p>
<p>Falls du keinen Account registrieren möchten, ignoriere bitte diese E-Mail.</p>
