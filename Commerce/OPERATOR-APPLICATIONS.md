# Betreiberanträge

Lokaler Antragsweg für Gastronomie, Kultur, Bildung und andere Einrichtungen.
Kein Vertragsabschluss, keine Abo-Buchung, keine automatische Kontoanlage oder E-Mail.

- `LinkUUp/OperatorApply`: aktives Mitglied stellt Antrag; vorhandene Location muss ihm bereits gehören. Ohne Location entsteht ausschließlich ein offener Antrag.
- `LinkUUp/OperatorReview`: Administrator prüft Übersicht und entscheidet mit Antragsnummer und Versionsnummer. Eigene Anträge dürfen nicht selbst entschieden werden. Die Freigabe verlangt ausdrücklich „Prüfung und Vertragsklärung abgeschlossen“ und einen Prüfvermerk. Diese Bestätigung dokumentiert manuelle Arbeit, keinen juristischen Nachweis.
- Neue Einrichtung: Team klärt Zuständigkeit und Vertrag und legt die Location im regulären Weg an/ordnet sie zu. Danach kann der Nutzer einen Antrag für diese eigene Location stellen. Der ursprüngliche Antrag ohne Location wird mit entsprechendem Prüfvermerk abgeschlossen; er gewährt niemals Rechte.
- Zulässige Übergänge: pending → approved/rejected, approved → revoked. Gleichzeitige Entscheidungen werden über Zeilensperre und Versionsnummer abgefangen. Für dieselbe User-/Location-Zuordnung wird kein weiterer Antrag angenommen, solange einer pending oder approved ist.
- Beide schreibenden Methoden verwenden GDO-AntiCSRF. Die Service-Ebene prüft Benutzerstatus und Eigentümer im aktuellen DB-Zustand; Adminrechte werden frisch aus der Datenbank unter Sperre gelesen.
- Widerruf/Ablehnung bleibt auch bei inzwischen deaktiviertem Antragsteller möglich. Freischaltung verlangt aktiven Antragsteller und unverändert eigene, nicht gelöschte Location.

## Installation / Einbindung

Neue Klasse `LUP_OperatorApplication` ist in `Module_LinkUUp::getClasses()` registriert.
Nach normalem GDO-Bootstrap kann ausschließlich die neue Tabelle mit
`Database::instance()->createTable(LUP_OperatorApplication::table())` angelegt werden.
Rückgabewert und tatsächlich entstandenes Schema prüfen. Nicht den gesamten
LinkUUp-Installer ausführen; keine automatische Migration bestehender Tabellen.

Erforderlich sind InnoDB für `lup_operatorapplication`, `gdo_user`, `lup_room`;
für Entscheidungen zusätzlich `gdo_userpermission` und `gdo_permission`.
Bei fehlender Unterstützung verweigert der Service Schreiboperationen.

`OperatorApplications::approvalFor(string $uid, string $rid): bool` prüft aktuell
Freigabe, aktiven Benutzer und Eigentum. Fehlende/nicht-InnoDB-Antragstabelle
liefert false. Innerhalb einer bestehenden Transaktion wird FOR UPDATE verwendet.
Mutierende Gastro-Zugriffe müssen den Check innerhalb ihrer Transaktion ausführen
und bis Commit halten: Session → Gast und Owner (numerisch sortiert) → Room → Store → Freigabe.
Der Owner wird zunächst nur als Hinweis gelesen und nach Sperren der User unter
Room-Lock erneut geprüft; ein zwischenzeitlicher Wechsel führt zu 409. Review sperrt
User (numerisch sortiert) → Adminberechtigung → Room → Antrag; kein Store-Zugriff.
Ein alleiniger Vorabcheck außerhalb der Transaktion schützt nicht gegen Widerruf.

## Prüfstand

`php -d zend.assertions=1 -d assert.exception=1 tests/operator-applications-validation.php`
prüft 19 Eingabe-/Vorbedingungsfälle ohne Datenbank. PHP-Syntax geprüft.
`tests/operator-applications-db.php` bestand 23 isolierte MariaDB-Prüfungen
mit eigenem synthetischem Schema, einschließlich Fremdeigentum, Selbstfreigabe,
Adminrechten, manueller Bestätigung, veralteter Version, Widerruf eines
deaktivierten Antragstellers und konkurrierendem UPDATE auf gesperrte Freigabe.
Der Harness benötigt ausdrücklich `GASTRO_QA_SOCKET` unter
`/tmp/simion-gastro-db-*` und optional `GASTRO_QA_DB_USER`; er erzeugt und
entfernt ausschließlich sein zufälliges `simion_operator_qa_*`-Schema.
Nachfolgend im isolierten echten GDO-Schema geprüft: 10 HTTP-Antragsfälle mit CSRF, fremder Location, pending ohne Rechte, verweigerter Selbstfreigabe, erforderlicher Vertragsbestätigung, Adminfreigabe und Widerruf der Betreiberliste. 55 Gastro-HTTP-Fälle prüfen unter anderem Rollen, Standort und Eigentümerwechsel. Die vier unten beschriebenen Konkurrenzfälle sind gezielt; kein vollständiger Last-/Produktionsnachweis.

## Lockreihenfolge-Regression

`tests/transaction-owner-locks.php`: vier bestandene Prüfungen mit getrennten
Prozessen/Verbindungen: Gast wartet am Owner-Userlock, paralleler Owner-/Reviewpfad
kann Room sperren und abschließen; Gast folgt erfolgreich. Wechsel der
Eigentümerzuordnung zwischen Hinweis und Room-Lock wird mit 409 abgelehnt;
ein neuer Abruf übernimmt keine alte Freigabe. Eigene temporäre DB wird entfernt.
Die bestehenden 48 TransactionStore-Prüfungen bestanden ebenfalls (unveränderter
Testcode in temporärer Kopie, ausschließlich DB-Nutzer für isolierten Socket angepasst).
