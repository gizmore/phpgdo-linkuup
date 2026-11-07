> Aktueller Integrations- und Prüfstand: [INTEGRATION-REVIEW.md](INTEGRATION-REVIEW.md). Ältere Prüfnotizen unten bleiben historisch; sie sind keine aktuelle Freigabe.

# Gastro: lokaler Pilot mit Euro-Testguthaben

Stand 07.11.2026, Feature-Branch `simion/gastro-local-20261106`. Kein Live-Release.

## Jetzt implementiert

`Method/GastroLocal.php` verbindet die lokale App mit der echten GDO-Sitzung und der aktuellen Owner-Zuordnung einer Location. Nur app.localhost und Loopback; Member erforderlich, schreibende Aktionen mit Origin und Session-CSRF. Betreiberaktionen prüfen den Owner serverseitig. Neue Bestellungen/Freigaben sind bei deaktivierter oder gelöschter Location gesperrt. Nicht freigegebene Menüdaten werden Gästen nicht ausgegeben; eigene Bestellsnapshots bleiben zugänglich.

`Ordering.php`: serverseitige Centpreise, Warenkorb, feste Tischzahlen, Freigabe/Pause, idempotente Bestellung, Statuswechsel und bestellbezogene Nachrichten.

`TestWallet.php`: getrenntes Euro-Testkonto je GDO-Nutzer, Cent-genaue Testaufladungen, Reservieren, Abbuchen, Freigeben und Erstatten; Journal und idempotente Wiederholungen. 20,00 EUR Testaufladung entsprechen 20,00 EUR Testguthaben. Bestands-Coins werden nicht gelesen oder verändert. Beim Aufladen höchstens1.000 EUR pro Vorgang und10.000 EUR verfügbares+reserviertes Testguthaben; keine Behauptung gesetzlicher Grenzwerte.

`TransactionStore.php` verwendet für den GDO-Adapter eine gemeinsame MariaDB/InnoDB-Transaktion:
Session → Benutzer → Location → Pilot-Ledger werden in fester Reihenfolge gesperrt.
Sessionzuordnung, Token, Ablaufzeit, CSRF, Benutzerstatus und aktueller Owner werden
vor der Buchung aus den gesperrten Zeilen geprüft. Bestellung und Testledger landen
im selben Commit; Fehler rollen zurück. CSRF-Erzeugung erfolgt unter Session- und Storelock im eigenen Pilotstore.
Der Adapter liest die GDO-Session nur; er verändert sess_data nicht. Der CSRF-Eintrag
ist an einen Hash aus Session-ID, Benutzer-ID, geprüftem Sessiontoken und aktueller
sess_auth-Generation gebunden. Fehlt das neue Sessionfeld, wird503 geliefert.
Alte Token werden nicht aus sess_data übernommen; nach Upgrade Ansicht neu laden.
CSRF-Einträge laufen nach höchstens einer Stunde ab (bei kürzerer
Sessionlebensdauer entsprechend früher). Polling verlängert sie nicht. Erfolgreiche
Zugriffe bereinigen abgelaufene/ungültige Einträge atomar; ohne Zugriffe läuft kein
Hintergrundjob. Maximal512 aktive Einträge: weitere Initialisierung wird mit503
abgewiesen, bestehende Einträge werden nicht verdrängt. Nach Ablauf liefert eine
Mutation403; die Ansicht muss mit state neu geladen werden. Die gemeinsame
16-MB-Pilotgrenze bleibt bestehen.
`schema.sql` bereitet ausdrücklich die isolierte Pilottabelle vor, ohne alte
JSON-Bestände zu importieren. Der Store ist ein gemeinsamer, größenbegrenzter JSON-
Datensatz in InnoDB: geeignet für begrenzte Pilotprüfungen, keine Skalierungs- oder
Produktionszusage. Historischer `PilotStore.php` bleibt nur für den getrennten QA-Server.

**Aktivierungsvoraussetzung:** Alle vier beteiligten Tabellen müssen InnoDB sein.
Die untersuchte lokale GDO-Session-Tabelle ist MyISAM; der Adapter verweigert dort
Transaktionen. Keine bestehende Tabelle wurde konvertiert. Core-Sessionmigration,
Session-Commit-/WebSocket-Verhalten und ein durchgehender GDO-HTTP-Test sind separat
zu prüfen. Kein Fallback auf ungeschützten Dateispeicher.

UI: dezenter Karte-Zugang im vorhandenen Location-Werkzeugbereich; schließbares Overlay, Karte/Warenkorb/Tisch/Testguthaben/Bestellstatus. Fehlendes Testguthaben führt zur Aufladung und zurück zum Warenkorb. Sichtbare Bestellansichten aktualisieren sich alle fünf Sekunden, mit Fehler-/Abrufanzeige und Dialogschutz; kein Echtzeit-Push. Ursprüngliche Location-CSS unverändert.

## Prüfweg

`php tests/ordering-domain.php` (45 Assertions), `php tests/test-wallet.php` (40 Assertions).
Lokaler Browser-Prüfserver mit ausdrücklich simulierten Identitäten:
`LUP_WALLET_QA=1 LUP_WALLET_APP=<App>/preview/gastro LUP_WALLET_STATE=<privater-Pfad>.json php -S 127.0.0.1:8768 tools/gastro-wallet-preview/router.php`
Dieser Server ist ausschließlich CLI/Loopback, nicht als öffentliche API verwenden. Er prüft UI+Domain, NICHT echte GDO-Anmeldung. Auf app.localhost ist anonymer Zugriff mit403 geprüft; vollständiger echter Owner-/Gastdurchlauf steht aus.

## Vor Live noch offen

Echte Rollen-/Session-/CSRF-End-to-End-Tests, Core-Sessionmigration auf InnoDB, Prüfung des integrierten Transaktionsadapters, Betreiberberechtigung und Paketverkauf, de/en für gesamte eingebettete Oberfläche (derzeit deutsch), Echtzeitstatus, Anwesenheits-/Tischbestätigung, Steuern/Belege/Kasse und Zahlungsanbieter. CSV-Import ist im lokalen Adapter als begrenzte UTF-8-Vorschau mit anschließender ausdrücklicher Übernahme angeboten. Keine freie PDF-/Foto-Kartenverarbeitung.

PayPal ist kein bloßer Austausch des Testauflade-Buttons: Aufladung erst nach verifiziertem, dedupliziertem Provider-Ereignis; Lieferung und erfolgreicher Capture als getrennte Zustände, Fehler/Retry/Reconciliation, Händlerzuordnung und Auszahlungen. Testaufladung darf in einer öffentlichen API niemals aktiv sein. Keine Produktionsfreigabe durch diese Tests.

## Rechtliche Vorprüfung – Deutschland, Abruf06.11.2026

Die Benennung als Coin oder Guthaben und ein1:1-Kurs entscheiden nicht über die aufsichtsrechtliche Einordnung. Elektronisch gespeicherter monetärer Wert, gegen Geld ausgegeben und von anderen angenommen, kann E-Geld sein. Ein gemeinsames Guthaben verschiedener Bars braucht daher vor Echtgeldbetrieb eine konkrete Prüfung des Geldflusses und eines geeigneten Zahlungsdienstleistermodells.
- Bundesbank, E-Geld: https://www.bundesbank.de/de/aufgaben/unbarer-zahlungsverkehr/ueberwachung/e-geld-603588 (Seiteninhalt gelesen).
- §2 ZAG: https://www.gesetze-im-internet.de/zag_2018/__2.html (Seiteninhalt gelesen). Begrenztes Netz/Warenspektrum sind Voraussetzungen, kein automatisch passendes Label; Absatz2 regelt die Anzeige oberhalb1Mio EUR in zwölf Monaten. Die technische Dienstleisterausnahme setzt u.a. voraus, nicht in Besitz der Gelder zu gelangen.
Keine Feststellung einer Ausnahme oder Rechtsfreigabe für LinkUUp. Erneut prüfen, sobald Händlervertrag, Gelderverwahrung und Provider feststehen.


## Betreiberabsicherung – Soll/Ist vom 06.11.2026 (Auftrag5818)

Vergleich nur anhand offizieller Dokumentation, keine fremden Konten getestet:
- Square beschränkt Teamrechte auf zugewiesene Locations: https://api.squareup.com/help/ca/en/article/5822-employee-permissions
- Stripe beschreibt wiederholungssichere Mutationen: https://docs.stripe.com/api/idempotent_requests
- Stripe empfiehlt separate Teamzugänge und Zwei-Faktor-Authentifizierung: https://support.stripe.com/questions/two-step-authentication-requirement

| Anforderung | Lokaler Stand | Offen vor produktiver Nutzung |
| --- | --- | --- |
| Locationgebundene Rechte | Aktueller GDO-Owner, keine Rechte aus Browserparametern; Access prüft jede Aktion | Echte Sessionmatrix, bestätigter Betreiber-Onboardingprozess und spätere Mitarbeiterrechte |
| Kontosperre | Nur Member ohne user_deleted; keine eigene user_enabled-Spalte im untersuchten GDO_User | Tatsächliche Logout-/Sperr- und Sessionwiderrufswege durchgehend testen, MFA/erneute Anmeldung für sensible Änderungen |
| Inaktive Location | Neue Konfiguration, Bestellung und Testaufladung gesperrt; bestehende Aufträge weiter abwickelbar | Verhalten der künftigen administrativen Sperrgründe festlegen |
| Nachvollziehbarkeit | Neue Bestellungen/Statuswechsel mit Akteur und UTC-Zeit; gleiche Wiederholung erzeugt keinen zweiten Eintrag | Kein revisionssicheres Auditarchiv; alte Historie wird nicht erfunden |
| Atomare Buchung | Gemeinsame InnoDB-Transaktion im neuen Adapter; 48 isolierte DB-Prüfungen | Core-Sessionmigration, echter GDO-/Proxy-E2E, Restore-/Crashprüfung und Providerintegration |
| Wiederholung/Konkurrenz | Domain-/Dateitests plus acht parallele DB-Aufladeretries; DB-Rennen gegen Logout/Benutzer-/Location-Deaktivierung | Echte HTTP-/WebSocket-Konkurrrenz im vollständigen Stack |

Prüfungen: `php tests/access-policy.php`, `php tests/ordering-domain.php`,
`php tests/test-wallet.php`, `php tests/pilot-concurrency.php`.
Diese Tests beweisen keine echte Anmeldung, Betreiberverifikation oder Echtgeldfähigkeit.
Gekauftes Betreiberpaket darf allein keine fremde Location übernehmen oder deren Rechte gewähren.
Keine gemeinsam geteilten Betreiberpasswörter vorsehen; Delegation braucht eigene Identitäten und Rechte.


## Historischer localhost-HTTP-Test vor DB-Umstellung (Auftrag5823)

Vier getrennte GDO-Formanmeldungen und zwei markierte QA-Locations:41 Checks bestanden
für Locationrechte, fremde Bestellungen/Guthaben, CSRF/Origin, gefälschte Rollenfelder,
Retry, pausierte Location, bestehende Abwicklung, doppelte Lieferung und gelöschten
Betreiber bei offener Sitzung. Kein QA-Rollenparameter ersetzt diese Anmeldungen.
Die Testkonten werden anschließend deaktiviert; Testpasswörter/Cookies bleiben nur
im Arbeitsspeicher des Testlaufs. Lokale Belege liegen bei Simion unter
research/gastro-auth-5823. WebSocket-App-Login und mobile Gesamtbedienung sind damit
noch nicht abschließend geprüft.

Behobene Integrationsfehler: lokaler /backend-Proxy verwendet Host localhost;
der Adapter akzeptiert diesen nur mit App-Origin oder passendem App-Referer
(weiterhin Loopback erforderlich). Historisch wurde das CSRF-Token in der Sitzung persistiert; der neue
Transaktionsadapter hält es ausschließlich im eigenen Pilotstore. Fehler werden über GDO Application gesetzt; das Framework normalisiert
manche Ablehnungen auf409. Der private Pilotordner muss dem tatsächlichen lokalen
VHost-Benutzer gehören (Modus0700), nicht pauschal www-data.
Benutzerstatus und Owner werden nach Erwerb des Pilotlocks uncached ausDB gelesen.

Weiterhin konkrete PR-/Produktgrenzen:
- room_minion_bot_control ist im lokalen Room-Modell, aber nicht im lokalen Schema.
  Keine automatische Migration/Fremdänderung durchgeführt; zuständigen Maintainer abstimmen.
- Historischer JSON-Pilot: DB-Kontosperre/Room-Deaktivierung und Dateicommit
  teilen keine DB-Transaktion. Der aktuelle GDO-Adapter verwendet TransactionStore
  mit gemeinsamen InnoDB-Sperren; der separate JSON-Vorschauweg beweist dies nicht.
  GDO-Session-Lebenszyklus und vollständige Integration bleiben ungeprüft.
- Betreiberverifikation, MFA, Mitarbeiterrollen, vollständige de/en und Provider fehlen.
- Ein Provider muss Händler-Onboarding und Zahlungszustände serverseitig bestätigen;
  ein erfolgreicher Browser-Redirect darf keine Gutschrift auslösen.
  Quelle: https://docs.stripe.com/connect/marketplace
- Rechteprüfung bei jeder Anfrage, standardmäßig verweigern und Ressourcenbezug prüfen:
  https://cheatsheetseries.owasp.org/cheatsheets/Authorization_Cheat_Sheet.html

## Explizite Pilotfreigabe (default-off)
Die GDO-Route ist ohne die serverseitige boolesche Konstante
`GDO_LINKUUP_GASTRO_LOCAL_PILOT === true` gesperrt, auch bei direktem execute-Aufruf.
Testaufladungen benötigen zusätzlich `GDO_LINKUUP_GASTRO_TEST_TOPUP === true`.
Beide Freigaben sind standardmäßig aus; weder HTTP-Header noch UI-Parameter
aktivieren sie. Keine Aktivierung durch diesen PR oder den Installer.
Nur in einer **separaten, isolierten Testinstallation** konfigurieren. Niemals im
öffentlich erreichbaren Backend einschalten. Dessen Proxy muss die Pilotroute
zusätzlich am Eingang sperren; Host/Origin/Loopback sind keine Authentifizierung
des Netzwerkursprungs. Eine reale Proxy-Konfiguration wurde hier nicht freigegeben.
Die früheren HTTP-Rollentests liefen vor diesem zusätzlichen Gate; sie belegen
keinen aktuellen aktivierten Gesamtablauf. DB-/Session-Rennen bleiben offen.

## Reproduzierbare isolierte DB-Prüfung (07.11.2026)
`GASTRO_QA_SOCKET=/tmp/simion-gastro-db-<test>/db.sock php tests/transaction-store.php`
setzt eine separate MariaDB mit Unix-Socket-Authentifizierung für den aufrufenden
OS-Nutzer und ohne Netzwerklistener voraus. Das Skript legt ausschließlich ein
neues zufälliges QA-Schema an, verwendet keine echten Konten und behält es für
Nachprüfung. 48 Prüfungen: Rollback, doppelte und acht parallele Aufladung,
Sessiontoken/CSRF/Ablauf, Logout und Sperrung, MyISAM-/Nested-Transaction-Ablehnung,
reale konkurrierende Sperren und CSRF-Erzeugung. Kein GDO-/App-E2E-Nachweis.
Schemaeinrichtung nicht auf Produktion ausführen; keine Zugangsdaten im Repository.

## Sicherheitsmatrix für spätere echte Zahlungen – noch nicht implementiert
Vorhandene Coins bleiben unverändert, Testledger ist kein echtes Guthaben.
Providergeführte Händlerkonten sind ein Vorschlag; Verwahrung, Aufladungen und
Auszahlung sowie regulatorische Zuständigkeit müssen vor Umsetzung entschieden sein.

| Quelle / belegtes Prinzip | Prüfkriterium für LinkUUp |
| --- | --- |
| [PayPal Idempotency](https://developer.paypal.com/api/rest/reference/idempotency/) | Stabile IDs pro Operation; Retry nach Timeout darf nicht doppelt buchen; API-spezifische Gültigkeit beachten |
| [PayPal Webhooks](https://developer.paypal.com/api/rest/webhooks/) | Signatur prüfen; Event-ID und Capture-ID deduplizieren; Betrag/Währung/Nutzer/Händler abgleichen; Browser-Erfolg ist kein Capture |
| [OWASP Transaktionsautorisierung](https://cheatsheetseries.owasp.org/cheatsheets/Transaction_Authorization_Cheat_Sheet.html) | Betrag und Empfänger serverseitig binden; nach Änderung neu bestätigen; Berechtigung direkt bei Ausführung prüfen |
| [NIST SP800-63B](https://pages.nist.gov/800-63-4/sp800-63b.html) | Phishingresistente Betreiberanmeldung und abgesicherte Wiederherstellung/Step-up vor sensiblen Änderungen planen |
| [Binance, vorläufiger Bericht 03.11.2026](https://www.binance.com/en-NG/blog/community/3229153443759566763) | Getrennte Entwicklungs-/Zahlungsrechte, keine Zahlungsgeheimnisse auf Entwicklerrechnern, Lieferkette prüfen; Not-Aus muss auch bestehende Delegationen widerrufen |

Binance nennt rund6,34Mrd **US-Dollar**, mehrere kompromittierte Systeme und eine
noch laufende Untersuchung; daraus folgt keine nachgewiesene Überlegenheit unserer
Architektur. Für LinkUUp abzuleiten: Wiederanlauf/Restore testen, Auszahlungen separat
berechtigen und begrenzen, Änderungen an Zahlungsempfängern gesondert bestätigen,
Providerbuchungen regelmäßig abgleichen, Refund/Dispute/verspätete Events testen.
Dies ist ein Anforderungskatalog, keine implementierte Bank-/Payment-Sicherheit.

## Historischer Zwischenstand vor Session-Integration (nicht aktueller Freigabestatus)

Der folgende damalige Arbeitsstand bleibt zur Nachvollziehbarkeit erhalten. Aktueller lokaler Prüfstand: INTEGRATION-REVIEW.md und OPERATOR-APPLICATIONS.md.

Beide PRs blieben Draft, bis die folgenden offenen Punkte mit tatsächlichen
Ergebnissen dokumentiert sind. Die 48 isolierten SQL-Prüfungen ersetzen keinen
dieser vollständigen GDO-Abläufe. Zuständigkeit: Simion Gastro/App, Mira Core/WS.

| Prüfung | Erwartung | Stand |
| --- | --- | --- |
| Geladene Session mit geändertem sess_data, paralleler Logout, späteres GDO_Session::commit | sess_user bleibt abgemeldet; alte sess_data/CSRF werden nicht wiederhergestellt | Offen, Mira |
| Zwei WebSockets desselben Nutzers, getrennte Sessions; eine widerrufen | Widerruf wirkt auf richtige Verbindung; kein Überschreiben der Sessionzuordnung | Offen, Mira |
| Gastro-Buchung gegen Logout/Sperrung im echten Request-Lebenszyklus | Eindeutige Reihenfolge, kein nach Widerruf autorisierter Folgeauftrag, kein doppelter Ledgerposten | Offen, gemeinsam |
| Isolierte Schemaeinrichtung einschließlich Session-Engine und Room-Modell | Installations-/Upgradeweg und Wiederherstellung geprüft; keine stillen Produktionsänderungen | Offen |
| Aktuelle GDO-Routen mit Gast, fremdem Gast, Owner und fremdem Owner | Fremde Location/Order/Wallet und ungültiges CSRF abgewiesen; zulässige Abläufe erfolgreich | Nach neuem Speicher/Gates erneut offen |
| Öffentlicher Proxy mit manipulierbaren Host-/Origin-Headern | Pilotroute extern gesperrt, auch mit gültiger Session/CSRF; Standardkonfiguration aus | Offen |
| App: Tisch → Karte → Warenkorb → Bestellung → Betreiberstatus → Gaststatus | Durchgehend tatsächliche Rollen/DB, Status automatisch sichtbar, Fehler/Retry ohne Doppelbuchung | Offen |

Für jeden Abschluss festhalten: Core-/Modul-Commit, isolierte Konfiguration,
Testaufruf, beobachtetes Ergebnis und verbliebene Einschränkung. Keine Passwörter,
Cookies oder Sessiontokens als Beleg ablegen. Keine Passwortframes an ungeprüfte
WebSocket-Laufzeiten senden. Pilot-Abnahme ist keine Echtgeld-/Providerfreigabe.

### Reproduzierter Session-Baselinefehler (07.11.2026)
`GASTRO_QA_SOCKET=/tmp/simion-gastro-db-<test>/db.sock GASTRO_QA_CORE=/pfad/zum/phpgdo php tests/session-lifecycle-baseline.php`
prüft echte GDO_Session-/GDO-set/save/commit-Methoden mit zwei PHP-Prozessen.
Der DBMS-Link wird ausschließlich auf die isolierte Socket-Datenbank gesetzt;
keine protected/config.php, echten Konten oder vorhandenen Tabellen werden benutzt.
Ein zufälliges QA-Schema bleibt zur Nachprüfung erhalten.

Beobachtet im lokalen Core: Ein sauberes geladenes Objekt schreibt nach Logout
nichts zurück. Mit vor Logout geändertem sess_data stellt das spätere commit die
Daten wieder her; sess_user bleibt NULL. Das Skript charakterisiert bewusst den
fehlerhaften Ausgangszustand (`security_pass: false`), ist KEIN grüner Sicherheits-
oder Freigabetest und muss nach einem Fix in einen Regressionstest umgebaut werden.
Echte GDO-Objekte/Save-Hooks werden verwendet; HTTP-Logout-Methode, Benutzer-/Logout-
Hooks, WebSockets, vollständige Installation und Cache-/Produktionskonfiguration
sind nicht abgedeckt. Kein nachgewiesener Login-Bypass. Core-Korrektur bleibt offen.

### Gastro-CSRF gegen verspäteten GDO-Commit abgesichert
`GASTRO_QA_SOCKET=/tmp/simion-gastro-db-<test>/db.sock GASTRO_QA_CORE=/pfad/phpgdo php tests/session-gastro-csrf.php`
prüft mit echtem GDO-Sessionobjekt und separatem Logout-Prozess: TransactionStore
schreibt keine sess_data, sein CSRF bleibt nach dirty GDO commit gültig, und nach
Logout wird dieselbe Identität abgewiesen. Der DB-Test prüft zusätzlich Rotation
des Sessiontokens und Ablehnung alter CSRF-/Sessiontokens. Kein HTTP-/WS-Gesamttest;
der allgemeine Core-Baselinefehler wird damit ausdrücklich NICHT behoben.

### Offener Authentifizierungswechsel
Wenn Logout und Login desselben Nutzers Session-ID und Sessiontoken unverändert
lassen, kann Gastro anhand dieses identischen Tupels den Anmeldewechsel nicht
erkennen. Die TTL begrenzt die Lebensdauer, behebt aber NICHT diese Bindungslücke.
Im gelesenen Login::Form::loginSuccess wird sess_user gespeichert und ein
UserAuthenticated-Hook ausgelöst; eine Tokenrotation ist dort nicht sichtbar.
Andere Hooks und der vollständige Login-Ablauf sind noch nicht geprüft.
Core muss eine geprüfte Rotation oder Auth-Generation bereitstellen; dies bleibt
vor gemeinsamer Freigabe offen. Kein eigener konkurrierender Core-Hook im Gastro-PR.

## Neuer lokaler Integrationsvertrag (noch nicht als Komplettstand veröffentlicht)
Der aktuelle Arbeitsstand verlangt eine32stellige sess_auth-Generation. Die
getrennten lokalen Session-/Login-Branches erneuern sie auch bei Wiederanmeldung
desselben Nutzers und leeren sess_data. Der aktualisierte echte GDO-Test belegt,
dass altes Gastro-CSRF dann abgewiesen und bei state neu erzeugt wird.48 isolierte
DB-Prüfungen bestehen einschließlich fehlender Generation (503).

Die vorher dokumentierte Wiederanmeldungs-Lücke ist damit im isolierten
Sessionmethoden-Test abgedeckt, aber NICHT im vollständigen HTTP-/WS-Login mit
allen Hooks. Die neuen Core-/Session-/Login-/WS-Branches sind noch nicht gemeinsam
bereitgestellt oder auf ein bestehendes Schema migriert. Der Modulstand bleibt
in Standardkonfiguration aus; kein alleiniger Backend-Pull als funktionierende
Gesamtintegration bezeichnen. Cookie-Sessionfixation, vollständiger Browserablauf,
Proxy-E2E und Restore-/Upgradeprüfung bleiben offen.
