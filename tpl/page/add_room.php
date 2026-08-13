<?php

use GDO\Form\GDT_Form;

/** @var GDT_Form $form */
?>
<section class="lup-room-composer">
	<header class="lup-room-composer-head">
		<div class="lup-room-composer-mark"><i class="fas fa-map-marked-alt"></i></div>
		<div>
			<small>LINKUUP · ORTSWERKSTATT</small>
			<h1>Neue Location anlegen</h1>
			<p>Jeder Eintrag beginnt mit einem echten Ort: Name, Kategorie, präziser Pin und ein sinnvoller Radius.</p>
		</div>
	</header>
	<aside class="lup-room-composer-guide" aria-label="Qualitätscheck">
		<span><i class="fas fa-check"></i> Exakte Adresse</span>
		<span><i class="fas fa-check"></i> Pin auf dem Gebäude</span>
		<span><i class="fas fa-check"></i> Radius passend zur Fläche</span>
	</aside>
	<div class="lup-room-composer-form">
		<?= $form->render() ?>
	</div>
</section>
