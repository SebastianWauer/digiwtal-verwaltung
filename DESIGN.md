# DESIGN.md

Verbindliche Designregeln der Verwaltung. Abgeleitet aus
`public/assets/css/` (admin-layout, admin-sidebar, admin-components,
admin-app) und `views/layout.php`. Was dort nicht entschieden ist, steht
hier als **TODO: ungeklärt** — nicht als Platzhalterwert.

## 1. Charakter

Eine Betriebskonsole, kein Produkt-Interface: fast schwarzer Grund, ein
einziges gerahmtes Panel, in dem alles stattfindet, und Text als
Hauptmaterial. Farbe ist Information und nicht Dekoration — die Fläche ist
durchgehend grau, und Grün, Gelb, Rot treten nur dort auf, wo sie einen
Zustand melden. Dichte geht vor Großzügigkeit: Tabellen, Kennwerte und
Statuszeilen sollen auf einen Blick lesbar sein, ohne dass gescrollt wird.

## 2. Farben

Dunkel ist der einzige Modus. `views/layout.php` setzt `data-theme="dark"`
fest; es gibt keine Hell-Palette und keine `prefers-color-scheme`-Abfrage,
und das bleibt so. Alle Tokens stehen auf `:root` in
[admin-layout.css](public/assets/css/admin-layout.css#L1-L25).

### Grundflächen

| Variable | Wert | Rolle |
|---|---|---|
| `--bg` | `#0f1012` | Seitengrund |
| `--panel` | `#131416` | Das tragende Panel, Modal, Auth-Karte |
| `--panel-border` | `#202226` | Rahmen des Panels |
| `--sidebar-bg` | `#0b0c0e` | Seitenleiste, dunkler als der Grund |
| `--sidebar-border` | `#1a1c20` | Trennkante der Seitenleiste |
| `--card` | `rgba(255,255,255,.03)` | Fläche auf dem Panel |
| `--card-border` | `rgba(255,255,255,.08)` | Rahmen dieser Flächen |

### Text

| Variable | Wert | Rolle |
|---|---|---|
| `--text` | `#f2f2f3` | Primärtext |
| `--muted` | `#b0b3b8` | Sekundärtext, Labels, Hinweise |

### Bedienelemente

| Variable | Wert | Rolle |
|---|---|---|
| `--input-bg` / `--input-border` | `rgba(255,255,255,.02)` / `.12` | Eingabefelder |
| `--btn-bg` / `--btn-border` | `rgba(255,255,255,.05)` / `.1` | Sekundäre Buttons |
| `--btn-hover` | `rgba(255,255,255,.08)` | Hover auf Button und Nav |
| `--active` | `rgba(255,255,255,.1)` | Aktiver Nav-Eintrag |

### Zustandsfarben

Bedeutungsträger, keine Markenfarben. Jede tritt in drei Abstufungen auf:
Vollton für Punkte und Balken, `.08` als Fläche, `.2`–`.28` als Rahmen.

| Variable | Vollton | Textfarbe auf Tönung | Bedeutung |
|---|---|---|---|
| `--success` | `#32d583` | `#9fe9be` / `#a5efc7` | healthy, erfolgreich |
| `--warning` | `#ffba49` | `#ffd79a` | degraded, veraltet, Achtung |
| `--danger` | `#ff6b6b` | `#ffb3b3` / `#ffc1c1` | down, timeout, Fehler |
| `--info` | `#6aa9ff` | `#b7d2ff` | neutraler Hinweis |

### Es gibt keine Akzentfarbe

Die primäre Aktion ist eine **weiße Fläche mit dunkler Schrift** und kommt
ohne Farbton aus. Weiß auf `#0f1012` ist der höchste verfügbare Kontrast und
konkurriert mit keiner Bedeutung — genau deshalb braucht die Anwendung keine
Markenfarbe im Interface.

Rollenverteilung, verbindlich:

| Rolle | Mittel |
|---|---|
| Primäre Aktion | Weiße Fläche `#f2f2f3`, Text `#101114` |
| Sekundäre Aktion | Transparent mit Rahmen (`--btn-bg` / `--btn-border`) |
| Blau (`--info`) | Informativ: Zustände, Links, aktive Markierungen |
| Rollenkennzeichnung | Farbe nur für Superadmin; Operator ist neutral |

### Blau steht an genau einer Stelle

`--info: #6aa9ff` ist die einzige Quelle. Alle Tönungen leiten sich per
`color-mix()` daraus ab, damit der Ton nirgends ein zweites Mal als Hex- oder
`rgba()`-Wert auftaucht:

| Variable | Ableitung | Verwendung |
|---|---|---|
| `--info-surface` | 8 % auf transparent | `.alert--info`, `.hint-card--info` |
| `--info-surface-strong` | 14 % auf transparent | `.btn--info` |
| `--info-border` | 20 % auf transparent | Rahmen der Meldungsflächen |
| `--info-border-strong` | 28 % auf transparent | Rahmen `.btn--info` |
| `--info-text` | 50 % auf Weiß | Schrift auf allen Info-Tönungen |

`--info-border` und `--info-border-strong` sind bei 1px Strichbreite nicht
auseinanderzuhalten (ΔL\* 4,8, aufgelöst auf einem Pixel). Sie bleiben
getrennt, weil ihre Zusammenlegung entweder den Button oder die
Meldungsflächen verändern würde — beides ist ausgeschlossen.

`color-mix()` setzt einen Browser ab 2023 voraus. Für eine interne
Betriebskonsole ist das gesetzt.

### Fokus

`--focus: #ffffff` ist ein **eigenes Token und bewusst nicht an eine
Bedeutung gekoppelt** — weder an Blau noch an einen Zustand. Fokus zeigt
Bedienung an, sonst nichts. Deckendes Weiß auf `#0f1012` ergibt den
höchstmöglichen Kontrast, und die Kontur unterscheidet sich von Hover nicht
nur im Ton, sondern in der Form: Hover füllt eine Fläche mit 8 % Weiß, der
Fokus setzt eine deckende Linie außerhalb des Elements.

### Farben außerhalb der Tokens

Im Code, aber nicht tokenisiert — beim nächsten Anfassen aufnehmen oder
ersetzen: `#d9dce3` (`.link-action`), `#8d93a0`, `#7d838c` (Placeholder),
`#69707a` (`.status-dot--muted`), `#090b0f` / `#e6edf7` (`.code-block`),
`#7c3aed` + `#dcc4ff` (Superadmin-Plakette).

## 3. Typografie

Kein Webfont. Der System-Stack ist eine bewusste Entscheidung: nichts
nachzuladen heißt kein Layoutsprung und keine Abhängigkeit vom Netz.

```
Fließtext  system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif
Mono       ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
           "Liberation Mono", "Courier New", monospace
```

Mono gilt für `code`, `pre` und `.mono` — Tokens, Pfade, Logs, IDs.

### Größen

| px | Verwendung |
|---|---|
| 30 | `.page-title` |
| 28 | `.auth-title` (25 unter 640px) |
| 18 | `.section-title`, `.dashboard-card__title`, `.status-modal__title` |
| 15 | `.info-card__value`, `.deploy-card__title` |
| 14 | Fließtext, Tabellenzellen, Buttons, Felder |
| 13 | `.section-copy`, Hinweiskarten, Aktionslinks |
| 12 | Labels, Plaketten, Tabellenköpfe |
| 11 | Eyebrow-Zeilen (`.deploy-card__eyebrow`) |

Gewichte: 500, 600, 650, 700, 750, 800. 700 ist das Arbeitsgewicht für
alles Hervorgehobene, 800 bleibt Titeln vorbehalten.

Versalien mit `letter-spacing: .04em`–`.08em` sind ausschließlich für
Kleinlabels (Plaketten, Tabellenköpfe, Eyebrows) reserviert, nie für
Fließtext.

### Zeilenhöhen

`1.2` Titel · `1.25` Auth-Eingaben · `1.5` Hinweise und Kurztexte ·
`1.6` Hinweiskarten und Modaltext.

- TODO: ungeklärt — `body` hat keine `line-height`; der Fließtext läuft
  mit dem Browserwert (~1.2). Für 14px-Text ist das zu eng.

### Zeilenlänge

Begrenzt wird heute nur über Containerbreiten: `--max` 1240px,
`.view-stack--narrow` 860px, `.auth-shell` 520px.

- TODO: ungeklärt — es gibt keine Begrenzung in `ch`. 860px bei 14px sind
  rund 100 Zeichen und damit deutlich über dem Lesbaren.

## 4. Abstände

**Zielskala für neuen Code: 4 · 8 · 12 · 16 · 24 · 32.** Wer eine neue Regel
schreibt, wählt daraus — keine Zwischenwerte, keine 2px-Schritte.

**Der Bestand wird nicht konvertiert.** Historisch benutzt sind, nach
Häufigkeit, 16, 12, 10, 8, 18, 14, 4, 6, 24, 22 px sowie Einzelfälle mit 2, 9,
11, 15, 20, 36. Diese Werte bleiben stehen, wo sie stehen: eine Umstellung
würde jede Ansicht anfassen, ohne dass jemand den Unterschied sähe.

Daraus folgt für die Praxis: Wer eine **vorhandene** Regel ändert, behält
deren Wert oder passt ihn an die Nachbarn an. Wer eine **neue** Regel anlegt,
nimmt die Zielskala. Beides steht nebeneinander, und das ist beabsichtigt.

Gesetzte Größen im Bestand: Panel-Innenraum 24px (18px unter 980px),
`.surface` 24px (`--tight` 18px), Kartenraster 14–18px, Formularfelder
15–16px, Seitenkopf-Abstand 22px.

## 5. Radien, Rahmen, Schatten

### Radien

| Variable | Wert | Verwendung |
|---|---|---|
| `--radius-lg` | `18px` | Panel, `.surface`, Dashboard-Karten |
| `--radius-md` | `14px` | Auth-Karte, Info-/Hinweiskarten |
| `--radius-sm` | `10px` | Button, Input, Nav-Eintrag |
| — | `999px` | Plaketten, Statuspunkte, Auth-Button |

Roh im Code und außerhalb der Tokens: `12px` (Alert, Code-Block,
Auth-Input), `16px` (Deploy-Karte, Modal-Panel), `4px` (Timeline-Balken).

- TODO: ungeklärt — ob 12px und 16px eigene Tokens bekommen oder auf 10/14/18
  zurückgeführt werden.

### Rahmen

Durchgehend `1px solid`. Einzige Ausnahme: `.data-table thead` mit `2px`
Unterkante. Rahmenfarbe folgt der Fläche (`--panel-border`,
`--card-border`, `--input-border`, `--btn-border`).

### Schatten

Genau einer, und nur auf schwebenden Flächen:

```css
--shadow: 0 10px 30px rgba(0, 0, 0, .35);   /* .panel, .status-modal__panel */
```

`.auth-card` setzt bewusst `box-shadow: none`.

Davon zu unterscheiden ist der **Statusring** — kein Schatten, sondern eine
Innenkante zur farblichen Markierung einer Karte:

```css
box-shadow: inset 0 0 0 1px rgba(<status>, .16 – .18);
```

Benutzt auf `.dashboard-card--green/--yellow/--red`,
`.deploy-meta-card--success/--warning`, `.deploy-card--install`.

Andere Schatten sind nicht zulässig — siehe Abschnitt 9.

## 6. Komponenten

### Buttons

Grundform: `inline-flex`, zentriert, `gap: 8px`, `min-height: 38px`,
`padding: 8px 14px`, `border-radius: 10px`, `font-size: 14px`,
`font-weight: 700`, `1px` transparenter Rahmen.

- `--primary` — fast-weiße Fläche `#f2f2f3`, Text `#101114`. Die eine
  Hauptaktion je Ansicht, ohne Farbton — siehe Abschnitt 2.
- `--secondary` — `--btn-bg` mit `--btn-border`, für alles Nebensächliche.
- `--info` / `--success` / `--warning` / `--danger` — getönte Fläche `.14`,
  Rahmen `.28`, heller Text. Nur wenn die Aktion den Zustand trägt.
- `--ghost` — transparent, `--muted`, wird erst im Hover sichtbar.
- `--sm` (32px, 13px), `--block` (volle Breite), `--linkish` (ohne Rahmen
  und Polsterung, Gewicht 600).

### Karten

`.surface` ist die Standardfläche: `--card` auf `--card-border`,
`--radius-lg`, 24px Polsterung (`--tight`: 18px). Spezialkarten
(`.info-card`, `.hint-card`, `.deploy-card`, `.dashboard-card`) folgen
demselben Muster mit eigener Polsterung und eigenem Radius.

Karten werden nicht geschachtelt tiefer als zwei Ebenen: Panel → Fläche.

### Eingaben

`.input`, `.select`, `.textarea`, `.file-input` teilen sich eine Form:
volle Breite, `--radius-sm`, `1px --input-border`, `--input-bg`,
`padding: 10px 12px`. `.textarea` mindestens 120px, nur vertikal
skalierbar. Label 14px/600, Hinweis darunter 12px in `--muted`.
Feld und Label stehen in `.field` mit 6px Abstand.

### Zustandsanzeigen

- `.status-pill` — 999px, 12px, Versalien, `min-height: 28px`. Varianten
  `healthy`, `degraded`, `down`, `timeout`, `unknown`, `na`, dazu die
  Rollen `superadmin`, `operator`.
- `.status-dot` — 14px Kreis in Grün/Gelb/Rot/Grau.
- `.timeline-bar` — 12×32px, `4px` Radius, Vollton.
- `.alert` / `.hint-card` — getönte Fläche `.08`, Rahmen `.2`.

### Zustände

**Hover** ist eine Flächenänderung: `background: --btn-hover`,
`border-color: --btn-border`, bei Buttons zusätzlich `opacity: .94`.

**Fokus** ist eine Kontur und damit formal etwas anderes als Hover —
verbindlich für Buttons und alle Eingaben:

```css
.btn:focus-visible,
input:focus-visible,
select:focus-visible,
textarea:focus-visible {
  outline: 2px solid var(--focus);
  outline-offset: 2px;
}
```

`:focus-visible`, nie `:focus`: der Ring gehört der Tastatur und darf beim
Mausklick nicht erscheinen. `outline: none` ist nur dort zulässig, wo eine
gleichwertige Kontur an seine Stelle tritt — nicht, um den Ring loszuwerden.

**Deaktiviert** heißt sichtbar tot: `opacity: .5`, `cursor: not-allowed`,
und jede Hover-Reaktion abgeschaltet (Farbwechsel wie Transform). Ein
deaktivierter Button darf sich unter dem Zeiger nicht lebendig anfühlen.

- TODO: ungeklärt — `.nav__item` setzt weiterhin `outline: none` und bleibt
  damit ohne eigenen Fokusring; die Regel oben deckt nur Buttons und
  Eingaben ab.
- TODO: ungeklärt — kein Stil für Ladezustände und keiner für ungültige
  Felder (`:invalid`); Fehler erscheinen ausschließlich als `.alert` oben.

### Icons

Heute geometrische Unicode-Zeichen in der Seitenleiste
(`▦ ◎ ◫ ◌ ≣ ? ↑ ⛁`, 18px, `--muted`, im aktiven Zustand `--text`).

**Zielzustand ist Inline-SVG.** Die Glyphen rendern je nach Betriebssystem
unterschiedlich breit, und `?` als Hilfe-Symbol ist ein Zeichen, kein Icon.
Neue Icons deshalb nur noch als Inline-SVG, `currentColor`,
`width: 18px`. Bestand wird bei Gelegenheit abgelöst.

## 7. Bewegung

Sparsam und ausschließlich als Rückmeldung auf Zeigerbewegung. Es gibt
keine Keyframes, keine Transforms, keine Ein- oder Ausblendungen.

```css
transition: opacity 120ms ease, background 120ms ease, border-color 120ms ease;
```

Animiert wird nur: `.btn` und `.nav__item`, und dort nur Deckkraft,
Flächen- und Rahmenfarbe.

Nicht animiert: Position, Größe, Panel- und Modalauftritt, Statuswechsel,
Tabellenzeilen, Zahlen.

- TODO: ungeklärt — `prefers-reduced-motion` wird nirgends abgefragt. Bei
  120ms auf zwei Elementen ist der Bedarf gering, entschieden ist es nicht.

## 8. Breakpoints

Ausschließlich `max-width`, mobile zuletzt.

| Grenze | Wirkung |
|---|---|
| `980px` | Seitenleiste rutscht über den Inhalt (Grid auf eine Spalte), Kante wechselt von rechts nach unten; Panel-Polsterung 24 → 18px, Außenrand 18 → 12px |
| `860px` | `.admin-user-setup` von zweispaltig auf einspaltig |
| `640px` | Auth-Karte und Logo kleiner, `.page-header` untereinander, `.form-grid--2` und `.split-grid--2` einspaltig, `.table-actions` linksbündig |

Tabellen laufen auf schmalen Geräten nicht um, sondern scrollen waagerecht
in `.table-wrap`.

## 9. Guardrails

Ausnahmslos:

1. **Keine Emoji als Icons.** Weder in der Oberfläche noch in Meldungstexten.
2. **Keine Farbverläufe als Flächen.** Flächen sind Volltöne oder
   Weiß-Transparenzen. Es gibt heute keinen einzigen `gradient` im CSS —
   das bleibt so.
3. **Keine zentrierten Textblöcke über zwei Zeilen.** `text-align: center`
   ist für Titel, Plaketten und die Auth-Karte reserviert.
4. **Keine Schatten außerhalb von Abschnitt 5.** Erlaubt sind `--shadow`
   auf schwebenden Flächen und der `inset`-Statusring. Sonst keiner.
5. **Die primäre Aktion höchstens einmal pro Screen.** Die weiße Fläche ist
   das stärkste Mittel der Oberfläche und trägt genau eine Aktion je Ansicht.
   Zustandsfarben zählen nicht mit — sie dürfen so oft auftreten, wie es
   Zustände gibt.
6. **Keine neuen Farben ohne Token.** Kein Hex-Wert direkt in einer Regel,
   wenn er eine Rolle hat (der Bestand in Abschnitt 2 ist die Altlast).
7. **Keine Abstände außerhalb des Satzes in Abschnitt 4.**
8. **Keine zweite Schriftfamilie.** System-Stack und Mono, sonst nichts.
9. **Kein helles Theme, keine Theme-Umschaltung.**
10. **Versalien nur für Kleinlabels**, nie für Sätze.
