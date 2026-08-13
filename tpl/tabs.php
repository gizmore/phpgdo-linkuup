<?php /** Focused LinkUUp back-office navigation. */ ?>
<nav id="tabs" class="lup-admin-nav" aria-label="LinkUUp Verwaltung">
	<a class="lup-admin-nav-brand" href="<?=href('LinkUUp', 'Main')?>" title="Verwaltungsübersicht"><span class="lup-admin-nav-mark"><i class="fas fa-link"></i></span><span>LinkUUp</span></a>
	<a href="<?=href('LinkUUp', 'Main')?>"><i class="fas fa-th-large"></i><span>Übersicht</span></a>
	<a href="<?=href('LinkUUp', 'Rooms')?>"><i class="fas fa-map-marked-alt"></i><span>Locations</span></a>
	<a href="<?=href('LinkUUp', 'AddRoom')?>"><i class="fas fa-plus-circle"></i><span>Ort hinzufügen</span></a>
	<a href="<?=href('LinkUUp', 'CategoryList')?>"><i class="fas fa-shapes"></i><span>Kategorien</span></a>
	<a href="<?=href('LinkUUp', 'Statistics')?>"><i class="fas fa-chart-line"></i><span>Auswertung</span></a>
</nav>
