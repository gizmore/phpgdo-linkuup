<?php ?>
<section class="lup-admin-hub">
	<header class="lup-admin-hub-head">
		<span class="lup-admin-hub-mark"><i class="fas fa-layer-group"></i></span>
		<div>
			<small>LINKUUP VERWALTUNG</small>
			<h1>Orte und Inhalte verwalten</h1>
			<p>Lege Locations an, halte Kategorien sauber und sieh, was in deinen Räumen passiert.</p>
		</div>
	</header>
	<nav class="lup-admin-actions" aria-label="LinkUUp Verwaltung">
		<a class="lup-admin-action lup-admin-action-primary" href="<?=href('LinkUUp', 'AddRoom')?>"><i class="fas fa-plus"></i><span><strong>Neue Location</strong><small>Ort, Radius und Kategorie anlegen</small></span><b>+</b></a>
		<a class="lup-admin-action" href="<?=href('LinkUUp', 'Rooms')?>"><i class="fas fa-map-marker-alt"></i><span><strong>Locations</strong><small>Bestehende Orte bearbeiten</small></span><b><i class="fas fa-arrow-right"></i></b></a>
		<a class="lup-admin-action" href="<?=href('LinkUUp', 'CategoryList')?>"><i class="fas fa-shapes"></i><span><strong>Kategorien</strong><small>Icons und Zuordnungen pflegen</small></span><b><i class="fas fa-arrow-right"></i></b></a>
		<a class="lup-admin-action" href="<?=href('LinkUUp', 'Statistics')?>"><i class="fas fa-chart-line"></i><span><strong>Statistiken</strong><small>Besuche und Nachrichten ansehen</small></span><b><i class="fas fa-arrow-right"></i></b></a>
	</nav>
</section>
