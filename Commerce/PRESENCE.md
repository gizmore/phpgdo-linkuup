# Karte lesen und vor Ort bestellen

Dieser lokale Pilot trennt die Veröffentlichung der Karte von der Bestellpause.
`publication` mit `enabled:true` setzt `menuPublished:true` und schaltet Bestellungen
ein. Eine spätere Pause behält `menuPublished:true`: angemeldete Mitglieder können
die Karte weiterhin lesen, auch aus der Ferne. Nie veröffentlichte Entwürfe sowie
Karten deaktivierter/gelöschter Locations werden Gästen nicht ausgegeben. Bei alten
Datensätzen gilt nur eine aktuell aktivierte Bestellfunktion als belegte frühere
Veröffentlichung; ein bereits pausierter alter Entwurf muss einmal freigegeben
werden, bevor seine Karte sichtbar wird.

## HTTP-Vertrag

`state` bleibt als GET ohne Standort lesbar. Für eine Standortprüfung unterstützt
es POST mit `action:"state"` und optionalem Objekt:

```json
{"position":{"latitude":52.0,"longitude":10.0,"accuracy":12,"timestamp":1700000000000}}
```

Die Zahlen sind nur ein Formatbeispiel, kein aktueller Standort. Der Client sendet
die ursprünglichen Werte und den Zeitstempel eines Browser-Geolocation-Fixes;
`timestamp` ist in Millisekunden seit Unix-Epoch, `accuracy` in Metern. Niemals
einen alten Fix durch Ersetzen seines Zeitstempels als frisch ausgeben. Karte lesen
fordert keine Standortfreigabe an. Neue Bestellungen (`place`) müssen den Fix erneut
mitsenden; vorherige erfolgreiche Prüfungen oder Chatmitgliedschaft reichen nicht.

`state` liefert `canOrder` und `orderBlockedReason`:

- `null`: Bestellung nach aktuellem Standort-/Freigabestand erlaubt.
- `ordering_paused`: Bestellungen pausiert oder Location deaktiviert.
- `location_required`: kein Fix mitgesendet (auch GET ohne Fix).
- `location_invalid`: unvollständige, nichtnumerische oder ungültige Koordinaten.
- `location_stale`: Fix älter als 120 Sekunden oder mehr als 5 Sekunden voraus.
- `location_inaccurate`: gemeldete Genauigkeit schlechter als 100 Meter.
- `outside_location`: außerhalb der serverseitigen Eintrittsgeometrie.
- `operator_verification_required`: Betreiberansicht wartet auf MFA.

Für die Geometrie wird `LUP_Room::isInChatRange(lat,lng,null)` benutzt: bestehendes
Polygon bzw. Radius und Eintrittstoleranz, aber kein gekaufter Distanzbonus. Die
gemeldete Genauigkeit erweitert die Grenze nicht. Diese Werte sind technische
Pilotgrenzen, keine Zusage fehlerfreier Anwesenheitserkennung.

Der HTTP-Adapter benutzt weder die flüchtigen WS-Mitgliedschaften/Positionen noch
`LUP_SignupGPS`: Dessen persistierte Koordinaten besitzen keinen Fix-Zeitstempel.
Die mitgesendeten Positionswerte werden hier nicht separat gespeichert. Sie sind
clientseitig manipulierbar; dies verhindert veraltete/verirrte normale Aufträge,
ist aber **kein fälschungssicherer physischer Anwesenheitsnachweis**.

Der Server prüft neue Aufträge in der gemeinsamen DB-Transaktion vor Reservierung
und Commit. Ein identischer Retry einer bereits gespeicherten Bestellung darf
weiterhin deren Ergebnis zurückgeben, auch nach Verlassen oder Pausieren; er
erzeugt keine neue Bestellung oder zweite Reservierung. Bestehende Aufträge und
Nachrichten bleiben den berechtigten Beteiligten zugänglich.

Lokaler Test: `php tests/order-presence.php`. Er prüft Validierung und Veröffentlichung,
nicht den echten Browser/HTTP-Verbund oder reale GPS-Qualität.
