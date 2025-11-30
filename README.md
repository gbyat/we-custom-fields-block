=== WE Custom Fields Block ===

**Contributors:** webentwicklerin  
**Tags:** custom fields, block, meta, gutenberg  
**Requires at least:** 5.8  
**Tested up to:** 6.4  
**Requires PHP:** 7.4  
**Stable tag:** 0.2.2
**License:** GPL-2.0-or-later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

# Custom Fields Block

A WordPress plugin that allows you to insert native WordPress custom fields as blocks with extensive typography and color options.

## Features

- **Dropdown Selection**: Choose from all available custom fields of the current post
- **Flexible Display**: Display as heading or paragraph
- **Typography Options**: Font size, weight, line height, and letter spacing
- **Color Options**: Configurable text and background colors
- **Spacing**: Margin and padding for top and bottom
- **Alignment**: Left, centered, right, and wide alignment
- **Responsive Design**: Optimized for all screen sizes

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Node.js 14 or higher (for development)
- npm or yarn (for development)

## Installation

### Quick Start

1. Download the plugin
2. Extract it to the `/wp-content/plugins/we-custom-fields-block/` folder
3. Activate the plugin in WordPress Admin → Plugins

### Compile Assets (Development)

```bash
# Navigate to the plugin directory
cd wp-content/plugins/we-custom-fields-block

# Install dependencies
npm install

# Compile assets
npm run build
```

### Development

#### Start Development Server

```bash
npm run start
```

This starts a development server that automatically compiles changes.

#### Format Code

```bash
npm run format
```

#### Linting

```bash
# JavaScript Linting
npm run lint:js

# CSS Linting
npm run lint:css

# JavaScript Linting with automatic fixes
npm run lint:js:fix
```

#### Update Dependencies

```bash
npm run packages-update
```

## Usage

### In the Block Editor

1. Add a new block
2. Search for "Custom Field" or "Custom Fields Block"
3. Select the desired custom field from the dropdown
4. Configure the display options:
   - **Display Type**: Paragraph or heading
   - **Typography**: Font size, weight, line height, letter spacing
   - **Colors**: Text and background color
   - **Spacing**: Margin and padding
   - **Alignment**: Left, centered, right, wide

### Creating Custom Fields

The plugin works with all native WordPress custom fields. You can create them via:

#### Manually via WordPress Admin

1. Edit a post
2. Scroll down to "Custom Fields"
3. Add new fields

#### Programmatically

```php
// Add custom field to a post
add_post_meta($post_id, 'my_field', 'My Value');

// Multiple values for a field
add_post_meta($post_id, 'my_field', 'Value 1');
add_post_meta($post_id, 'my_field', 'Value 2');
```

## Technical Details

### Supported Custom Fields

The plugin automatically detects all custom fields that:

- Do not start with an underscore (internal WordPress fields are ignored)
- Are assigned to the current post

### Block Attributes

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

### CSS Classes

The plugin automatically adds CSS classes:

- `.cfb-block` - Main container
- `.has-text-align-{alignment}` - Alignment
- `.has-text-color` - Text color set
- `.has-background` - Background color set

## Customization

### Custom CSS

You can customize the styling via your theme:

```css
/* Example: Custom styling for all custom field blocks */
.cfb-block {
  font-family: "Your Custom Font", sans-serif;
}

/* Example: Specific styling for headings */
.cfb-block h1,
.cfb-block h2,
.cfb-block h3 {
  border-bottom: 2px solid #007cba;
  padding-bottom: 0.5rem;
}
```

### Hooks and Filters

The plugin provides various hooks for developers:

```php
// Filter custom fields
add_filter('cfb_custom_fields', function($fields, $post_id) {
    // Your logic here
    return $fields;
}, 10, 2);

// Customize block output
add_filter('cfb_block_output', function($output, $attributes, $field_value) {
    // Your logic here
    return $output;
}, 10, 3);
```

## Browser Support

- Chrome 60+
- Firefox 55+
- Safari 12+
- Edge 79+

## WordPress Version

- WordPress 5.8 or higher
- PHP 7.4 or higher

## License

GPL v2 or later

## Troubleshooting

### Plugin Not Displaying

1. Check if the plugin is activated
2. Make sure assets are compiled (`npm run build`)
3. Check the browser console for JavaScript errors

### Custom Fields Not Displaying

1. Make sure custom fields exist
2. Check that fields do not start with an underscore
3. Make sure you are on the correct post

### Styling Issues

1. Check if CSS files are loaded
2. Make sure your theme supports block styles
3. Add custom CSS via your theme

### Performance Issues

1. Make sure assets are minified
2. Check the number of custom fields
3. Use caching plugins

## Updates

### Updating the Plugin

1. Download the latest version
2. Replace the old files
3. Run `npm install` and `npm run build`
4. Test functionality

## Security

- The plugin uses WordPress nonces for security
- All outputs are properly escaped
- Custom fields are validated
- No direct database queries without sanitization

## Performance Tips

1. Use caching for custom fields
2. Minimize the number of block instances
3. Use lazy loading for large datasets
4. Optimize CSS files

## Support

For questions or issues:

1. Check WordPress debug logs
2. Test the plugin in a clean WordPress installation
3. Create an issue in the [GitHub Repository](https://github.com/gbyat/we-custom-fields-block/issues)

## Changelog

The complete changelog can be found in the [CHANGELOG.md](./CHANGELOG.md) file.
