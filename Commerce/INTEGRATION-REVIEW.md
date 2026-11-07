# Gastro-Integration: lokaler Prüfstand vom 07.11.2026

Die drei folgenden lokalen Prüfungen des früheren Integrationsstands sind bestanden; sie ersetzen keinen Gesamtcheck der späteren Betreiberanträge, Präsenzprüfung und Lockreihenfolge: Mitgliedsanmeldung über HTTP mit Cookie-Rotation und anschließender echter WebSocket-Authentifizierung; Bestellung zwischen zwei getrennten Browser-Sitzungen; Offline-Schemamigration mit Wiederholung und Restore in getrennten Testdatenbanken.

Das ist ein **Review-Kandidat für einen isolierten Pilot**, keine Produktions- oder Echtgeldfreigabe. App-PR23 und Backend-PR10 gehören zusammen. Die Änderungen an Core, Session, Login und Websocket sind zusätzliche Abhängigkeiten: `integration-patches/` enthält sie zur separaten Maintainer-Prüfung. Das Backend installiert diese Patches nicht selbst.

## Belegte Abläufe

- 41 HTTP-Prüfungen mit echten GDO-Logins: Gast, fremder Gast, Betreiber und fremder Betreiber; Preise/Identität serverseitig, CSRF, fremde Location/Bestellung, gesperrter Betreiber, Deaktivierung und Abwicklung vorhandener Aufträge, Retry und einmalige Buchung.
- Chromium mit zwei getrennten Sitzungen: Tisch9, Artikel750Cent, Reservierung, automatisch sichtbare Betreiberbestellung, Annahme, Zubereitung, Lieferung. Verfügbares simuliertes Guthaben1250→500Cent; danach0Cent reserviert. Kein manuelles Aktualisieren der Bestelltafel nötig.
- Browser mit den tatsächlichen AuthSrvc/WebsocketSrvc/GWS_Message-Dateien und einem kleinen DI-/HTTP-Testadapter: HTTP-Mitgliedsanmeldung, rotierter Cookie, reale TCP-WebSocket-Anmeldung, HTTP-Logout, Ablehnung eines weiterhin offenen Sockets, erneute Anmeldung, Logout über AuthSrvc. Keine Passwortframes über WebSocket. Dies ist kein vollständiger Angular-SPA-Regressionstest.
- 48 isolierte DB-Prüfungen für den Transaktionsstore, 158 Rechte-,50 Domänen-,40 Wallet- und35 dateibasierte Konkurrenzprüfungen. Die dateibasierten Tests sind kein DB-Nachweis.
- 11 App-Tests für Auth-Reihenfolge, Fehler und Verbindungswechsel; Session-/CSRF-Regression mit echten GDO-Objekten und Widerrufsdispatch separat geprüft.
- Offline-Migration zweimal auf synthetischem Altschema ausgeführt; Sessions werden invalidiert, Ledger bleibt gleich. Separater Restore stellt Daten und ursprüngliche Tabellenengine wieder her.

## Installation und Rückweg zur Prüfung

1. In einer isolierten Kopie exakte Modulbasen und Patchmanifest prüfen. Patches zunächst mit `git apply --check` in den jeweils angegebenen Repositories prüfen. Nicht in fremde/main-Branches schreiben.
2. Core, Session, Login, Websocket, Backend und App gemeinsam bereitstellen. HTTP-/WS-Schreiber während Schemaänderungen stoppen. Vollständiges Backup erstellen und Restore in getrenntem Schema prüfen.
3. `Commerce/migrations/session-pilot-offline.sql` ist eine bewusst manuelle Offline-Migration. Sie setzt InnoDB für Session/User/Location, erweitert Token und ergänzt Authgeneration. Alle bestehenden Sessions werden abgemeldet, auch bei Wiederholung. DDL ist nicht transaktional rückrollbar.
4. Pilot-Ledger mit `Commerce/schema.sql` einrichten. Pilot und Testaufladung bleiben standardmäßig aus. Nur isoliert die beiden strikt booleschen Serverflags aktivieren. Optionaler `GDO_LINKUUP_GASTRO_ORIGIN` darf nur unterstützte lokale HTTP-Hosts mit gültigem Port enthalten; Header ersetzen diese Serverfreigabe nicht.
5. HTTP/WS gemeinsam starten, Mitgliedsanmeldung und Widerruf testen. Für Rückkehr alle Schreiber stoppen, zum gesicherten vollständigen Code-/Datenbankstand zurückkehren und Integrität prüfen. Niemals alte Sessiontabellen mit neuem Sessioncode mischen.

## Weiterhin außerhalb der Freigabe

- Keine echte Zahlung, Aufladung, Auszahlung oder Umwandlung bestehender Coins. Pilot-Guthaben bleibt ausdrücklich simuliert.
- Keine öffentliche Proxy-/Netzwerkfreigabe: Loopback allein schützt nicht hinter einem Reverse-Proxy. Default-off-Prüfungen sind kein Test einer realen öffentlichen Proxykonfiguration.
- Lokale Neuinstallation und Wiederholung wurden nach Korrektur veralteter Seed-Settings geprüft. Dies ersetzt kein Produktionsupgrade mit realen Altbeständen; dafür bleiben Migration und Restore getrennt zu prüfen.
- Registrierung, Gastkonto-Umwandlung, SPA-Start und getrennte Gerätesitzungen wurden lokal geprüft; die mitgelieferte WebSocket-Integration unterstützt mehrere Verbindungen. Reale externe OAuth-Zustimmung und produktive Laufzeit bleiben separat.
- Antwortpuffer schützt direkte GWS_Message-Antworten. Fremdmodul-Broadcasts und bereits ausgeführte Fachaktionen sind keine gemeinsame globale Transaktion. Kein universeller Core-Sicherheitsnachweis.
- Produktionsmigration, Lasttests, realer Wiederanlauf und unabhängige Maintainer-Abnahme bleiben separat. Keine automatischen Dienste oder Live-Tabellen verändert.

## Betreiberzugang in der bestehenden App (07.11.2026)

`Method/GastroVenues.php` ist eine reine GET-Liste eigener, nicht gelöschter Locations hinter denselben Local-Pilot-/Origin-Grenzen wie `GastroLocal`. Eine vorbereitete SQL-Abfrage prüft aktuellen Sessiontoken, Authgeneration, Ablauf und Mitgliedsstatus zusammen mit der Ownerzuordnung. Kein Staff-Override und kein frei wählbarer Ownerparameter. Ausgabe ausschließlich `id`, `name`, `enabled`; keine Bestellungen oder Geldwerte.

Die App zeigt damit „Mein Betrieb“ unter Entfernung kaufen ausschließlich für zugeordnete Owner mit genehmigtem Betreiberantrag und prüft die Liste beim Öffnen neu. Zugriff auf konkrete Bestellungen und Änderungen bleibt separat in `GastroLocal` unter Transaktionslocks und MFA geprüft. 14 echte lokale HTTP-Fälle für die Liste bestanden, einschließlich Rechteverlust, Mehrlocationfall und gesperrtem Owner. Das ist kein Betreiberabo-/Gewerbenachweis. Paketverkauf und Echtgeld bleiben deaktiviert.

## Aktueller Erweiterungsstand
Betreiberzugriff verlangt Eigentum UND aktuelle genehmigte Betreiberanträge sowie MFA. Antrag/Prüfung/Widerruf: OPERATOR-APPLICATIONS.md. Gastbestellungen brauchen einen aktuellen GPS-Fix (PRESENCE.md), Kartenpublikation und Bestellpause sind getrennt. 55 Gastro-HTTP-Prüfungen bestanden erneut nach der aktuellen Locksortierung; 10 Antrags-HTTP-Prüfungen bestanden nach Ergänzung des Statuslinks. Aktueller Build-E2E des lokalen Gastro-Ablaufs bestanden: zwei Browserkonten, Betreiber-MFA, Karte/Tische/Freigabe, Standort, simulierte Bestellung bis Lieferung, Polling, Schließen/Wiederöffnen/Zurück ohne JavaScript-Fehler. Registrierung/Logout/Relogin zusätzlich bestanden. 112 App-Tests und vier Owner-Lock-Konkurrenztests bestanden. Kein Produktions-/Echtgeldnachweis.

### Konfiguration bei Installation/Upgrade
Der LinkUUp-Installer setzt nun Payment.right_bar_your_orders auf 1: PaymentCredits benötigt das übergeordnete menu_payment. Bestehende Installationen müssen diese Einstellung bei der isolierten Übernahme ebenfalls prüfen; ein bloßer Dateipull ändert keine gespeicherte Konfiguration. Der HTTP-Antragslauf wurde mit dieser Einstellung geprüft. Die neue Tabelle lup_operatorapplication muss über den GDO-Modulinstaller angelegt werden. Keine automatische Übernahme in laufende Systeme.
