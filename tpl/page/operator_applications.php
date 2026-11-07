<?php
/** Private views: authenticated applicant or freshly checked admin only. */
$escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$status=['pending'=>'In Prüfung','approved'=>'Freigeschaltet','rejected'=>'Abgelehnt','revoked'=>'Freigabe widerrufen'];
?>
<section class="gdo-operator-applications">
<h2><?= $review?'Betriebsanträge':'Meine Betriebsanträge' ?></h2>
<?php if($review): ?><p>Nur nach geprüfter Zuständigkeit und abgeschlossener Vertragsklärung freischalten. Die Bestätigung dokumentiert die manuelle Prüfung; sie ist kein juristischer Nachweis. Neue Einrichtungen müssen zuerst durch das Team regulär angelegt und zugeordnet werden; danach ist ein Antrag für diese eigene Location nötig.</p><?php endif; ?>
<?php if(!$rows): ?><p>Noch keine Anträge.</p><?php endif; ?>
<?php foreach($rows as $row): ?>
<article><h3><?= $escape($row['oa_business']) ?></h3>
<p>Antrag #<?= $escape($row['oa_id']) ?> · <?= $escape($status[$row['oa_status']]??'Unbekannt') ?> · <?= $escape($row['oa_created']) ?></p>
<?php if($review): ?>
<dl><?php foreach(['oa_version'=>'Version','oa_user'=>'Antragsteller-ID','oa_room'=>'Location-ID','oa_kind'=>'Einrichtung','oa_address'=>'Anschrift','oa_contact'=>'Kontaktperson','oa_email'=>'E-Mail','oa_phone'=>'Telefon','oa_details'=>'Rolle und Nutzung','oa_review_note'=>'Letzter Prüfvermerk'] as $key=>$label): ?><dt><?= $escape($label) ?></dt><dd><?= nl2br($escape($row[$key])) ?></dd><?php endforeach; ?></dl>
<?php endif; ?>
</article>
<?php endforeach; ?>
<?= $form->render() ?>
</section>
