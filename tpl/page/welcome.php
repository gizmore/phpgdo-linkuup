<?php
declare(strict_types=1);

namespace GDO\LinkUUp\tpl\page;

use GDO\LinkUUp\Module_LinkUUp;

$module = Module_LinkUUp::instance();
$appURL = htmlspecialchars($module->cfgAppUrl(), ENT_QUOTES, 'UTF-8');
?>
<main class="lup-arrival">
	<section class="lup-arrival-hero">
		<div class="lup-arrival-grid" aria-hidden="true"></div><div class="lup-arrival-horizon" aria-hidden="true"></div><svg class="lup-arrival-mobile-arc-svg" viewBox="0 0 430 150" preserveAspectRatio="none" aria-hidden="true"><path class="lup-arrival-mobile-arc-base" d="M -20,138 C 95,4 292,4 450,138"/><path class="lup-arrival-mobile-arc-light" d="M -20,138 C 95,4 292,4 450,138"/><circle class="lup-arrival-mobile-arc-checkpoint checkpoint-one" cx="91" cy="52" r="3.5"/><circle class="lup-arrival-mobile-arc-checkpoint checkpoint-two" cx="220" cy="36" r="3.5"/><circle class="lup-arrival-mobile-arc-checkpoint checkpoint-three" cx="350" cy="72" r="3.5"/><circle class="lup-arrival-mobile-arc-pulse" r="5"><animateMotion dur="8.8s" repeatCount="indefinite" path="M -20,138 C 95,4 292,4 450,138"/></circle></svg>
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
		<div class="lup-arrival-copy"><p class="lup-arrival-kicker"><i class="fas fa-map-marker-alt"></i> ENTDECKEN · HINGEHEN · VERBINDEN</p><h1>Das Leben findet<br><em>nicht im Feed</em> statt.</h1><p>LinkUUp zeigt dir Orte, die du wirklich besuchen kannst. Damit aus einem Impuls ein echter Abend, ein Gespräch oder ein neues Gesicht wird.</p><div class="lup-arrival-actions"><a href="<?=$appURL?>"><i class="fas fa-map-marker-alt"></i> LinkUUp öffnen</a><a href="#lup-arrival-journey">Die Idee ansehen <i class="fas fa-arrow-down"></i></a></div></div>
	</section>

	<section id="lup-arrival-journey" class="lup-arrival-journey">
		<header><p>WIE LINKUUP DENKT</p><h2>Weniger scrollen.<br>Mehr erleben.</h2><span>Ein klarer Weg, der digital beginnt und bewusst im echten Leben weitergeht.</span></header>
		<ol><li><span>01</span><i class="fas fa-compass"></i><div><h3>Entdecken</h3><p>Finde Locations in deiner Nähe und orientiere dich an echten Orten statt an endlosen Feeds.</p></div></li><li><span>02</span><i class="fas fa-users"></i><div><h3>Entscheiden</h3><p>Sieh, wo etwas los ist, und behalte selbst im Blick, was du von dir zeigst.</p></div></li><li><span>03</span><i class="fas fa-walking"></i><div><h3>Hingehen</h3><p>Der Chat gehört zum Ort. Die Begegnung entsteht erst, wenn Menschen wirklich da sind.</p></div></li></ol>
		<a class="lup-arrival-next" href="#lup-arrival-principles"><span>Weiter zu „Auf deine Art“</span><i class="fas fa-arrow-down"></i></a>
	</section>

	<section id="lup-arrival-principles" class="lup-arrival-principles"><div class="lup-arrival-principles-copy"><p>AUF DEINE ART</p><h2>Orte statt Kulisse.<br>Kontrolle statt Druck.</h2><span>Standort und Sichtbarkeit haben einen klaren Zweck: Nähe verständlich machen. Du entscheidest, was dein Profil zeigt und wann du sichtbar sein möchtest.</span></div><div class="lup-arrival-principles-list"><div><i class="fas fa-map-pin"></i><span><b>Ortsbezogen</b><small>Der Kontext zählt.</small></span></div><div><i class="fas fa-user-shield"></i><span><b>Selbstbestimmt</b><small>Deine Einstellungen gelten.</small></span></div><div><i class="fas fa-heart"></i><span><b>Auf Augenhöhe</b><small>Begegnungen bleiben freiwillig.</small></span></div></div></section>

	<footer class="lup-arrival-footer"><div><span class="lup-arrival-mark"><i class="fas fa-link"></i></span><p><b>LinkUUp</b><br><small>Echte Begegnungen beginnen in deiner Nähe.</small></p></div><nav><a href="<?=href('Register', 'TOS')?>">Nutzungsbedingungen <i class="fas fa-arrow-up"></i></a><a href="<?=href('Core', 'Privacy')?>">Datenschutz <i class="fas fa-arrow-up"></i></a><a href="<?=href('Core', 'Impressum')?>">Impressum <i class="fas fa-arrow-up"></i></a></nav></footer>
</main>
