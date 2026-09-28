# Basalio Extension Points

This document is the single source of truth for the public extension surface of the Basalio plugin. Every hook, filter, and helper listed here is part of the stable public contract; anything not listed here is internal and may change without notice.

## Audience

Third-party developers and the Basalio Pro add-on extend the free plugin through these points. If you are building on Basalio, use only the points below.

---

## Admin

### `basalio_admin_menu`

Fires after the free Basalio top-level admin menu is registered.

```php
do_action( 'basalio_admin_menu' );
```

Pro uses this to register submenu pages and to override the parent page callback.

---

### `basalio_admin_tabs`

Filters the navigation tabs rendered on the Basalio admin dashboard.

```php
$tabs = apply_filters( 'basalio_admin_tabs', array() );
```

**Parameters**

- `$tabs` (`array`) Associative array of tab definitions keyed by slug. Each entry may contain:
  - `label` (`string`) Display label.
  - `url` (`string`) Tab URL.

**Returns**

`array` The filtered tab definitions.

---

## Design Tokens

### `basalio_tokens`

Filters the registered design token map before it is finalized.

```php
$tokens = apply_filters( 'basalio_tokens', array() );
```

**Parameters**

- `$tokens` (`array`) Token name => value pairs, e.g. `array( 'color-text' => '#111111' )`.

**Returns**

`array` The filtered token map.

---

### `basalio_tokens_registered`

Fires after the token map has been filtered and registered.

```php
do_action( 'basalio_tokens_registered', $tokens );
```

**Parameters**

- `$tokens` (`array`) Final token map.

---

### `basalio_token_css`

Filters the CSS custom-property block generated from the token map.

Since 0.6.6, the result is validated after this filter. Return one `:root { ... }` block containing custom properties only. Names must use ASCII letters, digits, underscores and hyphens (starting with a letter or underscore after `--`). Values support scalar keywords, numbers, dimensions, colors, and numeric/color functions such as `var()`, `calc()`, `clamp()` and `cubic-bezier()`. URLs, quoted strings, escapes, comments, other rules and HTML are rejected. Invalid declarations are omitted; an invalid stylesheet envelope produces no token stylesheet. The same value validation applies to `basalio_tokens` output and motion parameter fallbacks.

```php
$css = apply_filters( 'basalio_token_css', $css, $tokens );
```

**Parameters**

- `$css` (`string`) Generated CSS, e.g. `:root{ --color-text: #111111; }`.
- `$tokens` (`array`) Token map used to generate the CSS.

**Returns**

`string` The filtered CSS.

---

## Compliance Audit

### `basalio_audit_checks`

Filters the list of compliance audit checks shown in the dashboard.

```php
$checks = apply_filters( 'basalio_audit_checks', array() );
```

**Parameters**

- `$checks` (`array`) List of check definitions. Each entry typically contains:
  - `id` (`string`) Unique check identifier.
  - `label` (`string`) Human-readable label.
  - `status` (`string`) One of `pass`, `warn`, or `fail`.
  - `message` (`string`) Optional detail message.

**Returns**

`array` The filtered check list.

---

## Modules and Blocks

### `basalio_register_module`

Filters whether a discovered module should be registered as a block.

```php
$register = apply_filters( 'basalio_register_module', true, $module_name );
```

**Parameters**

- `$register` (`bool`) Whether to register the module. Return `false` to skip.
- `$module_name` (`string`) Directory name of the module, e.g. `text-reveal`.

**Returns**

`bool` Whether registration should proceed.

---

### `basalio_block_schema`

Filters the motion schema read from a module's `motion.schema.json` file.

```php
$schema = apply_filters( 'basalio_block_schema', $schema, $module_name );
```

**Parameters**

- `$schema` (`array`) Parsed schema array, or empty array if no schema exists.
- `$module_name` (`string`) Directory name of the module.

**Returns**

`array` The filtered schema.

---

### `basalio_block_supports`

Filters the block supports array merged into a registered block type.

```php
$supports = apply_filters( 'basalio_block_supports', array(), $module_name );
```

**Parameters**

- `$supports` (`array`) Block supports definition.
- `$module_name` (`string`) Directory name of the module.

**Returns**

`array` The filtered supports array.

---

### `basalio_block_variations`

Filters the block variations registered for a module.

```php
$variations = apply_filters( 'basalio_block_variations', array(), $module_name );
```

**Parameters**

- `$variations` (`array`) List of variation definitions. Each must contain a `name` key to be registered.
- `$module_name` (`string`) Directory name of the module.

**Returns**

`array` The filtered variations list.

---

### `basalio_block_render`

Filters the final HTML rendered by a Basalio block.

```php
$html = apply_filters( 'basalio_block_render', $html, $attributes, $module_name );
```

**Parameters**

- `$html` (`string`) Rendered block HTML.
- `$attributes` (`array`) Block attributes.
- `$module_name` (`string`) Directory name of the module.

**Returns**

`string` The filtered HTML.

---

## Motion Pipeline

The motion system is driven by per-module `motion.schema.json` files. The free plugin loads, validates, and converts those schemas into block attributes and front-end CSS variables. Pro and third-party code can extend motion without editing module files through the hooks below.

### `basalio_tokens`

Filters the registered design token map before it is finalized. The free plugin registers default motion tokens (`duration-*`, `easing-*`, `motion-scroll-threshold`, `motion-reveal-distance`) that schemas resolve to CSS variables. Pro overrides these defaults through this filter.

```php
add_filter(
	'basalio_tokens',
	function( $tokens ) {
		$tokens['duration-base'] = '500ms';
		return $tokens;
	}
);
```

---

### `basalio_block_schema`

Filters the motion schema read from a module's `motion.schema.json` file. Modules that ship with a schema expose their parameters through this filter, and Pro's configurator UI renders controls from the filtered schema. Adding a parameter here requires no changes to `block.json`, `render.php`, or the editor script.

```php
add_filter(
	'basalio_block_schema',
	function( $schema, $module_name ) {
		if ( 'text-reveal' === $module_name ) {
			$schema['parameters']['stagger'] = array(
				'label'   => 'Stagger',
				'type'    => 'range',
				'default' => 50,
				'min'     => 0,
				'max'     => 200,
				'step'    => 10,
			);
		}
		return $schema;
	},
	10,
	2
);
```

---

### `basalio_block_render`

Filters the final HTML rendered by a Basalio block, including any motion CSS variables injected from `motionParams`.

```php
add_filter(
	'basalio_block_render',
	function( $html, $attributes, $module_name ) {
		if ( 'text-reveal' === $module_name ) {
			$html = str_replace( 'data-basalio-motion-reveal', 'data-basalio-motion-reveal data-basalio-motion-custom', $html );
		}
		return $html;
	},
	10,
	3
);
```

---

## Public Helpers

### `basalio_has_block()`

Determine whether the current front-end request contains a Basalio block.

```php
$has_block = basalio_has_block();
```

This helper checks the current singular post content, the active block template, and any blocks rendered so far in the request. The result is cached for the duration of the request.

**Returns**

`bool` True when a Basalio block is present.

#### `basalio_has_block`

Filters the result of `basalio_has_block()`.

```php
$has_block = apply_filters( 'basalio_has_block', $has_block );
```

**Parameters**

- `$has_block` (`bool`) Current detection result.

**Returns**

`bool` The filtered result.

---

## Versioning Policy

Extension points follow WordPress semantic-versioning conventions for hooks:

- Removing an existing hook or changing its signature is a major version bump.
- Adding a new hook or filter is a minor version bump.
- Bug fixes that do not change signatures are patch version bumps.

When in doubt, pin your extension to `Requires Basalio:` in the plugin header.
