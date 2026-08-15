<?php
declare(strict_types=1);

namespace GDO\LinkUUp\tpl\page;

use GDO\LinkUUp\Module_LinkUUp;

$module = Module_LinkUUp::instance();
$appURL = htmlspecialchars($module->cfgAppUrl(), ENT_QUOTES, 'UTF-8');
?>
<main class="lup-arrival">
	<div class="lup-arrival-world" aria-hidden="true"><i class="lup-arrival-world-pin fas fa-map-marker-alt"></i><b class="lup-arrival-world-dot dot-one"></b><b class="lup-arrival-world-dot dot-two"></b><b class="lup-arrival-world-dot dot-three"></b><b class="lup-arrival-world-dot dot-four"></b></div>
	<section class="lup-arrival-hero">
		<div class="lup-arrival-grid" aria-hidden="true"></div><div class="lup-arrival-horizon" aria-hidden="true"></div><div class="lup-arrival-ambient" aria-hidden="true"><i></i></div>
		<div class="lup-arrival-hero-map" aria-hidden="true"><span class="lup-arrival-hero-pin"><i class="fas fa-map-marker-alt"></i></span><b class="lup-arrival-hero-dot dot-a"></b><b class="lup-arrival-hero-dot dot-b"></b><b class="lup-arrival-hero-dot dot-c"></b></div>
		<div class="lup-arrival-constellation" aria-hidden="true">
			<span class="lup-arrival-orbit orbit-one"></span><span class="lup-arrival-orbit orbit-two"></span>
			<span class="lup-arrival-constellation-core"><i class="fas fa-map-marker-alt"></i></span>
			<span class="lup-arrival-constellation-node node-one"><i class="fas fa-coffee"></i></span>
			<span class="lup-arrival-constellation-node node-two"><i class="fas fa-music"></i></span>
			<span class="lup-arrival-constellation-node node-three"><i class="fas fa-users"></i></span>
			<span class="lup-arrival-route route-one"></span><span class="lup-arrival-route route-two"></span>
			<span class="lup-arrival-float-card card-two"><i></i> LIVE · LOKAL</span>
		</div>
		<div class="lup-arrival-brand"><span class="lup-arrival-mark"><i class="fas fa-link"></i></span><b>LINKUUP</b><small>BEGEGNUNGEN IN DEINER NÄHE</small></div>
		<div class="lup-arrival-copy"><p class="lup-arrival-kicker"><i class="fas fa-map-marker-alt"></i> ENTDECKEN · HINGEHEN · VERBINDEN</p><h1>Das Leben findet<br><em>nicht im Feed</em> statt.</h1><p>LinkUUp zeigt dir Orte, die du wirklich besuchen kannst. Damit aus einem Impuls ein echter Abend, ein Gespräch oder ein neues Gesicht wird.</p><div class="lup-arrival-actions"><a href="<?=$appURL?>"><i class="fas fa-map-marker-alt"></i> LinkUUp öffnen</a><a href="#lup-arrival-journey" data-lup-scroll>Die Idee ansehen <i class="fas fa-arrow-down"></i></a></div></div>
	</section>

	<section id="lup-arrival-journey" class="lup-arrival-journey">
		<header><p>VOM BLICK ZUM MOMENT</p><h2>Weniger suchen.<br>Mehr ankommen.</h2><span>LinkUUp führt nicht in den nächsten Feed, sondern zum nächsten echten Ort – klar, lokal und auf deine Art.</span></header>
		<ol><li><span>01</span><i class="fas fa-compass"></i><div><h3>Entdecken</h3><p>Ein Ort, ein Gefühl, ein klarer Anfang.</p></div></li><li><span>02</span><i class="fas fa-users"></i><div><h3>Entscheiden</h3><p>Sieh, was passt – ohne endlos zu suchen.</p></div></li><li><span>03</span><i class="fas fa-walking"></i><div><h3>Hingehen</h3><p>Der Moment beginnt außerhalb des Displays.</p></div></li></ol>
		<a class="lup-arrival-next" href="#lup-arrival-categories" data-lup-scroll><span>Kategorien ansehen</span><i class="fas fa-arrow-down"></i></a>
	</section>

	<section id="lup-arrival-categories" class="lup-arrival-categories">
		<header class="lup-arrival-section-head"><p>ORTE MIT KONTEXT</p><h2>Finde den Ort,<br>der zu dir passt.</h2><span>Die Kategorien zeigen nicht nur einen Namen: Sie machen auf einen Blick klar, welche Art von Begegnung dich dort erwartet.</span></header>
		<div class="lup-arrival-category-grid">
			<article class="lup-arrival-category lup-arrival-category-bar"><i class="fas fa-cocktail"></i><span><b>Bar</b><small>Anstoßen. Reden. Bleiben.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-cafe"><i class="fas fa-coffee"></i><span><b>Café</b><small>Kaffee, Gespräche, Pause.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-club"><i class="fas fa-compact-disc"></i><span><b>Club &amp; Disco</b><small>Beat an. Nacht los.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-culture"><i class="fas fa-theater-masks"></i><span><b>Kultur</b><small>Ideen, Bühne, Bücher.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-education"><i class="fas fa-school"></i><span><b>Bildung</b><small>Wissen trifft Menschen.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-sport"><i class="fas fa-futbol"></i><span><b>Sport</b><small>Zusammen aktiv werden.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-health"><i class="fas fa-hospital"></i><span><b>Gesundheit</b><small>Hilfe, wenn sie zählt.</small></span></article>
			<article class="lup-arrival-category lup-arrival-category-city"><i class="fas fa-city"></i><span><b>Städte</b><small>Deine Stadt neu erleben.</small></span></article>
		</div>
		<a class="lup-arrival-next" href="#lup-arrival-principles" data-lup-scroll><span>Weiter zu „Auf deine Art“</span><i class="fas fa-arrow-down"></i></a>
	</section>

	<section id="lup-arrival-principles" class="lup-arrival-principles"><div class="lup-arrival-principles-copy"><p>DEIN RAUM. DEINE ENTSCHEIDUNG.</p><h2>Echte Orte.<br>Klare Kontrolle.</h2><span>LinkUUp macht Nähe verständlich, ohne dich dauerhaft sichtbar zu machen. Du entscheidest selbst, was dein Profil zeigt und wann du teilnehmen möchtest.</span></div><div class="lup-arrival-principles-list"><div><i class="fas fa-map-pin"></i><span><b>Ortsbezogen</b><small>Sichtbarkeit hat einen Ort, keinen Dauerstatus.</small></span></div><div><i class="fas fa-user-shield"></i><span><b>Selbstbestimmt</b><small>Du legst fest, was andere über dich sehen.</small></span></div><div><i class="fas fa-heart"></i><span><b>Auf Augenhöhe</b><small>Begegnungen entstehen freiwillig und respektvoll.</small></span></div></div></section>

	<footer class="lup-arrival-footer"><div><span class="lup-arrival-mark"><i class="fas fa-link"></i></span><p><b>LinkUUp</b><br><small>Echte Begegnungen beginnen in deiner Nähe.</small></p></div><nav><a href="<?=href('Register', 'TOS')?>">Nutzungsbedingungen <i class="fas fa-arrow-up"></i></a><a href="<?=href('Core', 'Privacy')?>">Datenschutz <i class="fas fa-arrow-up"></i></a><a href="<?=href('Core', 'Impressum')?>">Impressum <i class="fas fa-arrow-up"></i></a></nav></footer>
</main>
