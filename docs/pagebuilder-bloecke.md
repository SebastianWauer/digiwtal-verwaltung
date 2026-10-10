# Kundenspezifische Pagebuilder-Bloecke

Die Verwaltung verwaltet Blockfreigaben getrennt von Modul-Lizenzen.
Superadmins waehlen unter Kunde > Pagebuilder-Bloecke die erlaubten Typen.
Angeboten werden Standardbloecke und die Bloecke der Frontend-Zuordnung
des Kunden. Ein anderer Kunde kann keine Sport-Voice-Bloecke erhalten.

## Aktivierung

1. Migration `030_customer_pagebuilder_blocks.sql` in der Verwaltung anwenden.
2. Frontend-Key des Kunden pruefen: fuer Sport Voice `sport-voice`.
3. Die gewuenschten einzelnen Bloecke auswaehlen und speichern.
4. Den CMS-Rollout fuer genau diesen Kunden starten.

Die Freigabe wird nicht sofort in bereits laufende CMS-Instanzen uebertragen.
Der CI-Endpunkt liefert `pagebuilder.allowed_blocks`; der CMS-Workflow
synchronisiert daraus `PAGEBUILDER_ALLOWED_BLOCKS` und aus dem Frontend-Key
`PAGEBUILDER_CUSTOMER_KEY`. Lokale Provisionierung verwendet dieselben Werte.

Ohne gespeicherte Auswahl bleibt der bisherige Standardbestand erhalten.
Eine explizit leere Auswahl gibt keine Bloecke frei. Neue Kundenbloecke
werden niemals automatisch freigegeben. Das CMS prueft sowohl Typfreigabe
als auch Kundenkennung, auch bei verschachtelten Bloecken und Vorschauen.
Bestehende Live-Inhalte bleiben renderbar. Gesperrte Bloecke verhindern
weitere Speicherung, statt still entfernt zu werden.

## Neue Kundenbloecke

Jeder Typ hat eine eigene CMS-Klasse unter
`app/PageBuilder/CustomerBlocks/<Kunde>/` und einen eindeutigen Typnamen.
Der Kundenfrontend-Renderer und die Seitenvorlage verwenden denselben Namen.

Die Metadaten in `app/pagebuilder-blocks.json` muessen mit
`CMS/config/pagebuilder-blocks.json` uebereinstimmen. `customer_key`
ordnet einen Typ seinem Frontend-Key zu; der leere Wert kennzeichnet
Standardbloecke. `editor_type` ordnet den Typ einem vorhandenen Editor zu.
Neue Typen im CMS registrieren und beide Kataloge aktualisieren.

Pruefung ohne Kundendaten oder Netzwerk:

```sh
php scripts/check-pagebuilder-blocks.php /pfad/zum/CMS/config/pagebuilder-blocks.json
```

Der Test verwendet eine SQLite-Testdatenbank und eine Testanpassung fuer
MySQL-Upserts. Produktiv bleibt die Verwaltung bei MySQL.
