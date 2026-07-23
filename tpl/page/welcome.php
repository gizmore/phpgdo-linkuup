<?php

use GDO\LinkUUp\Module_LinkUUp;
use GDO\User\GDO_User;

?>
<h2>LinkUUp</h2>

<p>Welcome to LinkUUp, the local chat experience</p>

<p>You are authenticated as <?=GDO_User::current()->renderUserName()?>.</p>

<p>If you want to chat, you just might want to launch our <a href="<?php
	echo Module_LinkUUp::instance()->cfgAppUrl(); ?>" title="LinkUUp Chat App">App</a>.</p>

<!--
<p>
If you are the owner of an etablisment or want to get your location added, simply <a href="<?=href('Contact', 'Form')?>" title="Contact Us">contact us per email</a>.<br/>
</p>
 -->
