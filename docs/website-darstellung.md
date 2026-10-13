# Website-Darstellung pro Kunde

Superadmins waehlen unter Kunde > Website-Darstellung zwischen mehrseitiger
Website und Onepager. Die Auswahl ist getrennt von Blockfreigaben und
Navigationsreihenfolge. Andere Kunden werden nicht automatisch umgestellt.

## Rollout

1. Verwaltung ausrollen und Migration 031_customer_website_settings.sql anwenden.
2. Das gemeinsame CMS ausrollen. WEBSITE_MODE wird pro Kunde in der CMS-.env gesetzt.
3. Das aktualisierte Sport-Voice-Frontend ausrollen.

Die Migration erhaelt Sport Voice als Onepager. Alle anderen Kunden bleiben
standardmaessig mehrseitig. Spaetere Moduswechsel werden beim jeweiligen
Kunden-CMS-Rollout uebernommen, ohne die Inhalte oder Blockfreigaben zu aendern.

## CMS

Bei aktiviertem Modus gibt es unter Seiten > Onepager eine eigene Auswahl.
Die Startseite bleibt zuerst und ist nicht abwaehlbar. Alle anderen Seiten
koennen per Checkbox ein- oder ausgeschlossen sowie per Drag-and-drop oder
Pfeiltasten sortiert werden. Entwuerfe und Weiterleitungen sind nicht auswaehlbar.

Ohne gespeicherte Auswahl werden veroeffentlichte Inhaltsseiten vorbelegt,
Impressum und Datenschutz ausgeschlossen und Kontakt zuletzt einsortiert.
Nach dem Speichern gilt die ausdrueckliche Auswahl und Reihenfolge; neue Seiten
werden nicht automatisch hinzugefuegt. Ausgeschlossene Live-Seiten bleiben
separat erreichbar. Die Auswahl bleibt bei einem Moduswechsel erhalten.

Seitenwahl und Reihenfolge sind im CMS sofort wirksam, ohne erneuten Rollout.
Die oeffentliche CMS-API liefert website_mode und onepager_page_ids unter
settings/public. Andere Kundenfrontends muessen diese Felder ebenfalls
unterstuetzen, bevor ihr Modus praktisch umgeschaltet werden kann.

Pruefung: php scripts/check-website-mode.php
