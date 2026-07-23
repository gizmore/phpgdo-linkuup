<p><h1>Welcome to LinkUup</h1></p>
<p>Hello <?php
	echo $username; ?>,</p>

<p>To activate your account please visit the following link.</p>
<p style="text-align:center;">
    <br/>
    <a
            style="border-radius: 5px; padding: 15px 30px; background-image: linear-gradient(to top, #b712ff 0%, #9c1cd1 100%); text-decoration: none; color:#fff; font-size:18px;"
            href="<?php
			echo $activation_url; ?>">Activate</a>
    <br/>
    <br/>
</p>
<p>In case you do not want to register an account, please ignore this email.</p>
