<?php
declare(strict_types=1);

namespace GDO\LinkUUp\tpl\page;

use GDO\LinkUUp\Module_LinkUUp;

$module = Module_LinkUUp::instance();
$appURL = htmlspecialchars($module->cfgAppUrl(), ENT_QUOTES, 'UTF-8');
?>

<main class="lup-landing">
	<section class="lup-landing-hero">
		<img class="lup-landing-logo" src="<?=$module->wwwPath('images/lup-logo.png')?>" alt="LinkUUp">
		<div class="lup-landing-copy">
			<p class="lup-landing-kicker"><?=t('lup_landing_kicker')?></p>
			<h1><?=t('lup_landing_title')?></h1>
			<p class="lup-landing-lead"><?=t('lup_landing_lead')?></p>
			<div class="lup-landing-actions">
				<a class="lup-landing-primary" href="<?=$appURL?>"><?=t('lup_landing_open_app')?></a>
				<a class="lup-landing-secondary" href="<?=href('Contact', 'Form')?>"><?=t('lup_landing_contact')?></a>
			</div>
		</div>
	</section>

	<section class="lup-landing-values" aria-label="<?=t('lup_landing_values_label')?>">
		<article>
			<h2><?=t('lup_landing_nearby_title')?></h2>
			<p><?=t('lup_landing_nearby_text')?></p>
		</article>
		<article>
			<h2><?=t('lup_landing_together_title')?></h2>
			<p><?=t('lup_landing_together_text')?></p>
		</article>
		<article>
			<h2><?=t('lup_landing_control_title')?></h2>
			<p><?=t('lup_landing_control_text')?></p>
		</article>
	</section>
</main>
