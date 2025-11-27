=== Custom Fields Block ===

**Contributors:** webentwicklerin  
**Tags:** custom fields, block, meta, gutenberg  
**Requires at least:** 5.8  
**Tested up to:** 6.4  
**Requires PHP:** 7.4  
**Stable tag:** 0.1.0
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

# Custom Fields Block

Ein WordPress Plugin, das es ermöglicht, native WordPress Custom Fields als Blöcke mit umfangreichen Typografie- und Farboptionen einzufügen.

## Features

- **Dropdown-Auswahl**: Wählen Sie aus allen verfügbaren Custom Fields des aktuellen Posts
- **Flexible Darstellung**: Als Überschrift oder Absatz anzeigen
- **Typografie-Optionen**: Schriftgröße, -gewicht, Zeilenhöhe und Buchstabenabstand
- **Farboptionen**: Text- und Hintergrundfarbe konfigurierbar
- **Abstände**: Margin und Padding für oben und unten
- **Ausrichtung**: Links, zentriert, rechts und wide-Alignment
- **Responsive Design**: Optimiert für alle Bildschirmgrößen

## Voraussetzungen

- WordPress 5.8 oder höher
- PHP 7.4 oder höher
- Node.js 14 oder höher (für Entwicklung)
- npm oder yarn (für Entwicklung)

## Installation

### Schnellstart

1. Laden Sie das Plugin herunter
2. Entpacken Sie es in den `/wp-content/plugins/we-custom-fields-block/` Ordner
3. Aktivieren Sie das Plugin in WordPress Admin → Plugins

### Assets kompilieren (Entwicklung)

```bash
# In das Plugin-Verzeichnis wechseln
cd wp-content/plugins/we-custom-fields-block

# Abhängigkeiten installieren
npm install

# Assets kompilieren
npm run build
```

### Entwicklung

#### Entwicklungsserver starten

```bash
npm run start
```

Dies startet einen Entwicklungsserver, der automatisch Änderungen kompiliert.

#### Code formatieren

```bash
npm run format
```

#### Linting

```bash
# JavaScript Linting
npm run lint:js

# CSS Linting
npm run lint:css

# JavaScript Linting mit automatischer Korrektur
npm run lint:js:fix
```

#### Abhängigkeiten aktualisieren

```bash
npm run packages-update
```

## Verwendung

### Im Block-Editor

1. Fügen Sie einen neuen Block hinzu
2. Suchen Sie nach "Custom Field" oder "Custom Fields Block"
3. Wählen Sie das gewünschte Custom Field aus dem Dropdown
4. Konfigurieren Sie die Darstellungsoptionen:
   - **Anzeigetyp**: Absatz oder Überschrift
   - **Typografie**: Schriftgröße, -gewicht, Zeilenhöhe, Buchstabenabstand
   - **Farben**: Text- und Hintergrundfarbe
   - **Abstände**: Margin und Padding
   - **Ausrichtung**: Links, zentriert, rechts, wide

### Custom Fields erstellen

Das Plugin funktioniert mit allen nativen WordPress Custom Fields. Sie können diese erstellen über:

#### Manuell über WordPress Admin

1. Bearbeiten Sie einen Post
2. Scrollen Sie nach unten zu "Custom Fields"
3. Fügen Sie neue Felder hinzu

#### Programmatisch

```php
// Custom Field zu einem Post hinzufügen
add_post_meta($post_id, 'mein_feld', 'Mein Wert');

// Mehrere Werte für ein Feld
add_post_meta($post_id, 'mein_feld', 'Wert 1');
add_post_meta($post_id, 'mein_feld', 'Wert 2');
```

## Technische Details

### Unterstützte Custom Fields

Das Plugin erkennt automatisch alle Custom Fields, die:

- Nicht mit einem Unterstrich beginnen (interne WordPress-Felder werden ignoriert)
- Dem aktuellen Post zugeordnet sind

### Block-Attribute

```json
{
  "fieldKey": "string",
  "displayType": "paragraph|heading",
  "typography": {
    "fontSize": "number",
    "fontWeight": "string",
    "lineHeight": "number",
    "letterSpacing": "number"
  },
  "colors": {
    "textColor": "string",
    "backgroundColor": "string"
  },
  "spacing": {
    "marginTop": "number",
    "marginBottom": "number",
    "paddingTop": "number",
    "paddingBottom": "number"
  },
  "alignment": "left|center|right|wide"
}
```

### CSS-Klassen

Das Plugin fügt automatisch CSS-Klassen hinzu:

- `.cfb-block` - Hauptcontainer
- `.has-text-align-{alignment}` - Ausrichtung
- `.has-text-color` - Textfarbe gesetzt
- `.has-background` - Hintergrundfarbe gesetzt

## Anpassungen

### Custom CSS

Sie können das Styling über Ihr Theme anpassen:

```css
/* Beispiel: Custom Styling für alle Custom Field Blöcke */
.cfb-block {
  font-family: "Your Custom Font", sans-serif;
}

/* Beispiel: Spezifisches Styling für Überschriften */
.cfb-block h1,
.cfb-block h2,
.cfb-block h3 {
  border-bottom: 2px solid #007cba;
  padding-bottom: 0.5rem;
}
```

### Hooks und Filter

Das Plugin bietet verschiedene Hooks für Entwickler:

```php
// Custom Fields filtern
add_filter('cfb_custom_fields', function($fields, $post_id) {
    // Ihre Logik hier
    return $fields;
}, 10, 2);

// Block-Ausgabe anpassen
add_filter('cfb_block_output', function($output, $attributes, $field_value) {
    // Ihre Logik hier
    return $output;
}, 10, 3);
```

## Browser-Unterstützung

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## WordPress-Version

- WordPress 5.8 oder höher
- PHP 7.4 oder höher

## Lizenz

GPL v2 oder höher

## Troubleshooting

### Plugin wird nicht angezeigt

1. Überprüfen Sie, ob das Plugin aktiviert ist
2. Stellen Sie sicher, dass die Assets kompiliert wurden (`npm run build`)
3. Überprüfen Sie die Browser-Konsole auf JavaScript-Fehler

### Custom Fields werden nicht angezeigt

1. Stellen Sie sicher, dass Custom Fields vorhanden sind
2. Überprüfen Sie, ob die Felder nicht mit einem Unterstrich beginnen
3. Stellen Sie sicher, dass Sie sich im richtigen Post befinden

### Styling-Probleme

1. Überprüfen Sie, ob die CSS-Dateien geladen werden
2. Stellen Sie sicher, dass Ihr Theme die Block-Styles unterstützt
3. Fügen Sie Custom CSS über Ihr Theme hinzu

### Performance-Probleme

1. Stellen Sie sicher, dass die Assets minifiziert sind
2. Überprüfen Sie die Anzahl der Custom Fields
3. Verwenden Sie Caching-Plugins

## Updates

### Plugin aktualisieren

1. Laden Sie die neueste Version herunter
2. Ersetzen Sie die alten Dateien
3. Führen Sie `npm install` und `npm run build` aus
4. Testen Sie die Funktionalität

## Sicherheit

- Das Plugin verwendet WordPress-Nonces für Sicherheit
- Alle Ausgaben werden ordnungsgemäß escaped
- Custom Fields werden validiert
- Keine direkten Datenbankabfragen ohne Sanitization

## Performance-Tipps

1. Verwenden Sie Caching für Custom Fields
2. Minimieren Sie die Anzahl der Block-Instanzen
3. Verwenden Sie lazy loading für große Datenmengen
4. Optimieren Sie die CSS-Dateien

## Support

Bei Fragen oder Problemen:

1. Überprüfen Sie die WordPress Debug-Logs
2. Testen Sie das Plugin in einer sauberen WordPress-Installation
3. Erstellen Sie ein Issue im [GitHub Repository](https://github.com/gbyat/we-custom-fields-block/issues)

## Changelog

Das vollständige Changelog finden Sie in der Datei [CHANGELOG.md](./CHANGELOG.md).
