<?php
/** Private views: authenticated applicant or freshly checked admin only. */
$escape=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$status=['pending'=>'In Prüfung','approved'=>'Freigeschaltet','rejected'=>'Abgelehnt','revoked'=>'Freigabe widerrufen'];
?>
<section class="gdo-operator-applications <?= $review?'':'operator-application-entry' ?>">
<?php if(!$review && !$rows): ?>
<header class="operator-invitation">
 <span class="operator-kicker">LINKUUP FÜR DEINEN BETRIEB</span>
 <h1>Deine Karte.<br>Deine Gäste. Ein Ort.</h1>
 <p>Mach deine Speisen und Getränke direkt in deiner Location sichtbar. Verwalte Karte, Tische und Bestellungen in einem gemeinsamen Bereich.</p>
 <a class="operator-start" href="#operator-application-form">Location anmelden <span aria-hidden="true">→</span></a>
</header>
<section class="operator-benefits" aria-label="Deine Möglichkeiten">
 <article><h2>Einfach aktuell halten</h2><p>Artikel, Preise und Verfügbarkeit selbst bearbeiten.</p></article>
 <article><h2>Bestellungen zuordnen</h2><p>Artikel und Status übersichtlich je Tisch sehen.</p></article>
 <article><h2>Service selbst steuern</h2><p>Bestellungen gezielt freigeben oder pausieren.</p></article>
</section>
<section class="operator-showcase" aria-labelledby="operator-showcase-title">
 <header><h2 id="operator-showcase-title">Schau es dir an.</h2><p>Zwei Perspektiven auf deinen Betrieb.</p></header>
 <div class="operator-preview-choices">
  <a class="operator-preview-choice" href="/GDO/LinkUUp/img/operator/guest-preview.png" data-operator-preview="guest" aria-haspopup="dialog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 4h7l2 2 2-2h7v15h-7l-2 2-2-2H3Z M12 6v15 M6 8h3 M15 8h3 M6 12h3 M15 12h3"/></svg><span><strong>Die Karte für deine Gäste</strong><small>Auswahl, Preise und Warenkorb</small></span><span aria-hidden="true">↗</span></a>
  <a class="operator-preview-choice" href="/GDO/LinkUUp/img/operator/operator-preview.png" data-operator-preview="operator" aria-haspopup="dialog"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M3 4h18v16H3Z M3 9h18 M9 9v11 M15 9v11"/></svg><span><strong>Deine Bestellübersicht</strong><small>Neue Aufträge und laufender Service</small></span><span aria-hidden="true">↗</span></a>
 </div>
 <p class="operator-preview-note">Beispielansichten des Piloten. Zahlungen sind simuliert; die echte Zahlungsanbindung ist noch nicht freigegeben.</p>
</section>
<section class="operator-next"><h2>So geht es weiter.</h2><p>Du stellst deine Location vor. Wir prüfen deine Zuständigkeit und besprechen Angebot und Vertrag mit dir. Erst danach schalten wir deinen Betreiberzugang frei.</p></section>
<dialog class="operator-preview-dialog" aria-labelledby="operator-preview-title">
 <header><div><small>BEISPIELANSICHT · KEINE ECHTEN ZAHLUNGEN</small><h2 id="operator-preview-title">Vorschau</h2></div><button type="button" data-preview-close aria-label="Vorschau schließen">×</button></header>
 <div class="operator-preview-scroll"><img alt="" width="1120" height="1100"></div>
 <p>Erfundene Location und Beispieldaten aus der aktuellen Produktvorschau.</p>
</dialog>
<?php endif; ?>
<h2 id="operator-application-form"><?= $review?'Betriebsanträge':($rows?'Mein Betrieb':'Erzähl uns von deiner Location') ?></h2>
<?php if($review): ?><p>Nur nach geprüfter Zuständigkeit und abgeschlossener Vertragsklärung freischalten. Die Bestätigung dokumentiert die manuelle Prüfung; sie ist kein juristischer Nachweis. Neue Einrichtungen müssen zuerst durch das Team regulär angelegt und zugeordnet werden; danach ordnest du die Location-ID dem bestehenden offenen Antrag mit „Location zuordnen“ zu. Diese Aktion erteilt keine Freigabe. Anschließend die neue Version aus der Übersicht verwenden und gesondert prüfen. Eigene Anträge dürfen nicht selbst zugeordnet oder freigeschaltet werden.</p><?php endif; ?>
<?php if(!$review): ?><p class="operator-entry-intro"><?= $rows?'Hier findest du den aktuellen Stand deiner Anträge.':'Nenne uns deine Einrichtung und eine Kontaktperson. Wir melden uns zur weiteren Abstimmung.' ?></p><?php elseif(!$rows): ?><p>Noch keine Anträge.</p><?php endif; ?>
<?php foreach($rows as $row): ?>
<article><h3><?= $escape($row['oa_business']) ?></h3>
<p>Antrag #<?= $escape($row['oa_id']) ?> · <?= $escape($status[$row['oa_status']]??'Unbekannt') ?> · <?= $escape($row['oa_created']) ?></p>
<?php if(!$review): ?>
<p><?= $escape(match($row['oa_status']) {
 'pending' => $row['oa_room']===null ? 'Das Team prüft deine Angaben und ordnet deine Location zu. Für diesen Antrag brauchst du keinen zweiten Antrag.' : 'Deine Location ist zugeordnet. Die Prüfung ist noch offen.',
 'approved' => 'Dein Betrieb ist freigeschaltet. Öffne in der App „Mein Betrieb“ und bestätige dort deine sichere Anmeldung.',
 'revoked' => 'Die Betreiberfreigabe wurde widerrufen. Bitte kläre den nächsten Schritt mit dem Team.',
 default => 'Dein Antrag wurde abgelehnt. Bitte kläre offene Angaben mit dem Team, bevor du einen neuen Antrag stellst.'
}) ?></p>
<?php endif; ?>
<?php if($review): ?>
<dl><?php foreach(['oa_version'=>'Version','oa_user'=>'Antragsteller-ID','oa_room'=>'Location-ID','oa_kind'=>'Einrichtung','oa_address'=>'Anschrift','oa_contact'=>'Kontaktperson','oa_email'=>'E-Mail','oa_phone'=>'Telefon','oa_details'=>'Rolle und Nutzung','oa_review_note'=>'Letzter Prüfvermerk'] as $key=>$label): ?><dt><?= $escape($label) ?></dt><dd><?= nl2br($escape($row[$key])) ?></dd><?php endforeach; ?></dl>
<?php endif; ?>
</article>
<?php endforeach; ?>
<?php if(!$review && $rows): ?><details><summary>Weitere Location anmelden</summary><?= $form->render() ?></details><?php else: ?><?= $form->render() ?><?php endif; ?>
</section>
