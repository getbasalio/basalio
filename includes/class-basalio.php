<?php
/**
 * Basalio core loader.
 *
 * Responsibilities, and *only* these:
 *   1. Register the one shared JS core (handle: "basalio-core"), once.
 *   2. Auto-discover and register every module under /modules/*.
 *
 * It knows nothing about any specific module. Adding Module 2 or 3 means
 * dropping a folder in /modules, this file does not change.
 *
 * @package Basalio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Basalio
 */
final class Basalio {

	/**
	 * Singleton instance.
	 *
	 * @var Basalio|null
	 */
	private static $instance = null;

	/**
	 * Discovered module names.
	 *
	 * @var string[]
	 */
	private $module_names = array();

	/**
	 * Filtered block schemas keyed by block name.
	 *
	 * @var array<string, array>
	 */
	private static $block_schemas = array();

	/**
	 * Get (and lazily create) the singleton.
	 *
	 * @return Basalio
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire up hooks.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( 'Basalio_Admin', 'instance' ), 1 );
		add_action( 'init', array( 'Basalio_Tokens', 'get' ), 1 );
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this, 'register_modules' ), 10 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'block_editor_settings_all', array( $this, 'inject_editor_iframe_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_core_for_palette_content' ), 20 );
	}

	/**
	 * Load translations for the PHP side of the plugin.
	 *
	 * Hooked on init (never earlier) so it plays well with the just-in-time
	 * translation loading in current WordPress. Editor-script (JS) strings are
	 * wired separately in register_modules() via wp_set_script_translations().
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'basalio',
			false,
			dirname( plugin_basename( BASALIO_FILE ) ) . '/languages'
		);
	}

	/**
	 * Register shared, versioned assets.
	 *
	 * The core script is only *registered* here, never enqueued globally.
	 * Enqueuing happens per-render via basalio_enqueue_core() so it loads only
	 * on pages that actually contain a Basalio block.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_script(
			'basalio-core',
			BASALIO_URL . 'assets/js/basalio-core.js',
			array( 'wp-i18n' ),
			BASALIO_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// The core script builds a few user-facing strings at runtime (the
		// filterable-grid result count, the lightbox dialog labels). Without this
		// they render in English no matter the site locale, which previously
		// overwrote correctly translated server-rendered text the moment a visitor
		// clicked a filter. wp-i18n is a core dependency, not a third-party one.
		//
		// Note for release: JS translations resolve from .json files, so shipping a
		// new locale needs `wp i18n make-json languages/` after make-pot. Absent
		// those files the strings fall back to English, i.e. today's behaviour.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'basalio-core', 'basalio', BASALIO_PATH . 'languages' );
		}

		// Shared front-end styles (lightbox + clickable-image affordances),
		// enqueued next to the core script by basalio_enqueue_core().
		wp_register_style(
			'basalio-core',
			BASALIO_URL . 'assets/css/basalio-core.css',
			array(),
			BASALIO_VERSION
		);

		// Shared editor UI helper + styling. Every module's editor script lists
		// "basalio-editor" as a dependency (so it loads first and provides the
		// BasalioEd panel/field helpers), and the style is enqueued in the editor
		// via enqueue_editor_assets() below.
		wp_register_script(
			'basalio-editor',
			BASALIO_URL . 'assets/js/basalio-editor.js',
			array( 'wp-element' ),
			BASALIO_VERSION,
			true
		);
		wp_register_style(
			'basalio-editor',
			BASALIO_URL . 'assets/css/basalio-editor.css',
			array(),
			BASALIO_VERSION
		);
	}

	/**
	 * Enqueue the shared front-end assets for content that carries Basalio palette
	 * classes but no Basalio block.
	 *
	 * Block patterns are inserted as core blocks with Basalio color classes. The
	 * free plugin's namespace-based detection misses them, so without this hook a
	 * page containing only an expanded pattern would render with no Basalio palette.
	 *
	 * Pro token overrides are still loaded separately by the Pro add-on when active;
	 * this hook only guarantees the default palette is present.
	 *
	 * @return void
	 */
	public function maybe_enqueue_core_for_palette_content() {
		if ( ! function_exists( 'basalio_enqueue_core' ) ) {
			return;
		}

		// A Basalio block already triggers enqueue_core() during render.
		if ( function_exists( 'basalio_has_block' ) && basalio_has_block() ) {
			return;
		}

		// Current post content. On the front end get_post() returns the queried
		// post only on singular pages (the loop has not started this early), so
		// this is effectively scoped to singular content without requiring
		// is_singular(), which is not always available in test harnesses.
		$post = get_post();
		if ( $post && $this->content_has_basalio_palette( $post->post_content ) ) {
			basalio_enqueue_core();
			return;
		}

		// FSE block template.
		if ( function_exists( 'get_block_template' ) ) {
			$template = get_block_template( get_stylesheet() . '//' . basename( get_page_template() ), 'wp_template' );
			if ( $template && $this->content_has_basalio_palette( $template->content ) ) {
				basalio_enqueue_core();
			}
		}
	}

	/**
	 * Check whether the given content references Basalio palette classes.
	 *
	 * Core blocks colored with the Basalio palette carry class names like
	 * .has-basalio-surface-background-color, even when no Basalio block is present.
	 * The block editor also stores palette choices as JSON attributes such as
	 * "backgroundColor":"basalio-surface".
	 *
	 * @param string $content Post or template content.
	 * @return bool
	 */
	private function content_has_basalio_palette( $content ) {
		if ( false !== strpos( $content, 'has-basalio-' ) ) {
			return true;
		}

		return 1 === preg_match( '/"(?:backgroundColor|textColor|color|gradient)"\s*:\s*"basalio-[^"]+"/', $content );
	}

	/**
	 * Load the shared editor styling (and helper script) inside the block editor.
	 * The CSS only targets Basalio's own .basalio-ed-* editor chrome, so loading
	 * it editor-wide is harmless.
	 *
	 * Also localizes the validated motion schemas so the editor panel can render
	 * controls from motion.schema.json without hardcoding parameter names.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// The helper script must load in the editor's main frame, where block
		// registration runs.
		wp_enqueue_script( 'basalio-editor' );
		// The style is also enqueued for the outer frame as a fallback.
		wp_enqueue_style( 'basalio-editor' );

		$schemas = array();
		foreach ( self::$block_schemas as $block_name => $schema ) {
			if ( ! empty( $schema ) ) {
				$schemas[ $block_name ] = $schema;
			}
		}

		if ( ! empty( $schemas ) ) {
			wp_localize_script( 'basalio-editor', 'BasalioSchemas', $schemas );
		}
	}

	/**
	 * Inject the editor chrome CSS into the block-canvas iframe.
	 *
	 * WordPress 6+ renders the block canvas inside an iframe. A normally enqueued
	 * stylesheet does not reach inside it, so the BasalioEd panels would render
	 * unstyled. Adding the CSS to the editor "styles" settings loads it in the
	 * iframe, where the block edit() output actually lives.
	 *
	 * @param array $settings Block editor settings.
	 * @return array
	 */
	public function inject_editor_iframe_styles( $settings ) {
		$css_path = BASALIO_PATH . 'assets/css/basalio-editor.css';
		if ( file_exists( $css_path ) ) {
			$css = file_get_contents( $css_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
			if ( false !== $css ) {
				if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
					$settings['styles'] = array();
				}
				$settings['styles'][] = array( 'css' => $css );
			}
		}
		return $settings;
	}

	/**
	 * Auto-discover every module and register its block.
	 *
	 * Each module lives in its own folder under /modules and is fully described
	 * by a block.json (which points at its own render.php, style.css and
	 * editor script). register_block_type_from_metadata() reads that file, so
	 * this loop is completely module-agnostic.
	 *
	 * After registration the module's motion.schema.json (if present and valid)
	 * is used to inject a single `motionParams` object attribute with defaults
	 * matching the schema. Invalid schemas are logged and skipped; the block
	 * itself is still registered so the front end keeps working.
	 *
	 * @return void
	 */
	public function register_modules() {
		$modules = glob( BASALIO_PATH . 'modules/*/block.json' );

		if ( empty( $modules ) ) {
			return;
		}

		foreach ( $modules as $block_json ) {
			$module_name = basename( dirname( $block_json ) );

			if ( false === apply_filters( 'basalio_register_module', true, $module_name ) ) {
				continue;
			}

			$module_dir = dirname( $block_json );

			$schema = $this->load_block_schema( $module_dir );
			$schema = apply_filters( 'basalio_block_schema', $schema, $module_name );

			// Modules without a motion.schema.json are not motion blocks; skip
			// validation silently rather than logging an "empty schema" error.
			$schema_valid = empty( $schema ) ? true : self::validate_block_schema( $schema );

			$supports = apply_filters( 'basalio_block_supports', array(), $module_name );

			$variations = apply_filters( 'basalio_block_variations', array(), $module_name );

			$block_type = register_block_type_from_metadata( $module_dir );

			if ( $block_type instanceof WP_Block_Type ) {
				if ( ! empty( $supports ) ) {
					$block_type->supports = array_merge( (array) $block_type->supports, $supports );
				}

				self::$block_schemas[ $block_type->name ] = true === $schema_valid ? $schema : array();

				if ( ! empty( $variations ) ) {
					foreach ( $variations as $variation ) {
						if ( isset( $variation['name'] ) ) {
							register_block_variation( $block_type->name, $variation );
						}
					}
				}

				if ( true === $schema_valid && ! empty( $schema['parameters'] ) ) {
					$motion_params = array();
					foreach ( $schema['parameters'] as $param_name => $param ) {
						$motion_params[ $param_name ] = self::get_param_default_value( $param );
					}

					$block_type->attributes['motionParams'] = array(
						'type'    => 'object',
						'default' => $motion_params,
					);
				}

				if ( is_callable( $block_type->render_callback ) ) {
					$original = $block_type->render_callback;
					$block_type->render_callback = function( $attributes, $content, $block ) use ( $original, $module_name ) {
						$html = call_user_func( $original, $attributes, $content, $block );
						return apply_filters( 'basalio_block_render', (string) $html, $attributes, $module_name );
					};
				}

				foreach ( (array) $block_type->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'basalio', BASALIO_PATH . 'languages' );
				}
			}

			if ( true !== $schema_valid ) {
				error_log(
					'Basalio: invalid motion schema for ' . $module_name . ': ' .
					implode( '; ', $schema_valid->get_error_messages() )
				);
			}

			$this->module_names[] = $module_name;
		}
	}

	/**
	 * Validate a module's motion.schema.json.
	 *
	 * Returns true when the schema is well-formed, or a WP_Error describing
	 * every problem found. Invalid schemas are logged by the caller and do not
	 * block block registration; motion attribute injection is simply skipped.
	 *
	 * @param array $schema Parsed schema.
	 * @return true|WP_Error
	 */
	private static function validate_block_schema( $schema ) {
		$errors = new WP_Error();

		if ( ! is_array( $schema ) || empty( $schema ) ) {
			$errors->add( 'empty_schema', __( 'Schema is empty.', 'basalio' ) );
			return $errors;
		}

		$required_top = array( 'motionType', 'trigger', 'parameters' );
		foreach ( $required_top as $field ) {
			if ( ! isset( $schema[ $field ] ) ) {
				$errors->add(
					'missing_top_level_field',
					sprintf(
						/* translators: %s: schema field name */
						__( 'Schema missing required field: %s', 'basalio' ),
						$field
					)
				);
			}
		}

		if ( ! isset( $schema['parameters'] ) || ! is_array( $schema['parameters'] ) ) {
			return $errors;
		}

		$valid_types      = array( 'select', 'range', 'toggle', 'text' );
		$token_allow_list = self::get_token_allow_list();

		foreach ( $schema['parameters'] as $param_name => $param ) {
			if ( ! is_array( $param ) ) {
				$errors->add(
					'invalid_parameter',
					sprintf(
						/* translators: %s: parameter name */
						__( 'Parameter %s must be an object.', 'basalio' ),
						$param_name
					)
				);
				continue;
			}

			$param_required = array( 'label', 'type', 'default' );
			foreach ( $param_required as $field ) {
				if ( ! isset( $param[ $field ] ) ) {
					$errors->add(
						'missing_parameter_field',
						sprintf(
							/* translators: 1: parameter name, 2: field name */
							__( 'Parameter %1$s missing required field: %2$s', 'basalio' ),
							$param_name,
							$field
						)
					);
				}
			}

			if ( ! isset( $param['type'] ) || ! in_array( $param['type'], $valid_types, true ) ) {
				$errors->add(
					'invalid_parameter_type',
					sprintf(
						/* translators: 1: parameter name, 2: parameter type */
						__( 'Parameter %1$s has invalid type: %2$s', 'basalio' ),
						$param_name,
						isset( $param['type'] ) ? $param['type'] : __( 'missing', 'basalio' )
					)
				);
				continue;
			}

			switch ( $param['type'] ) {
				case 'select':
					if ( ! isset( $param['options'] ) || ! is_array( $param['options'] ) ) {
						$errors->add(
							'missing_select_options',
							sprintf(
								/* translators: %s: parameter name */
								__( 'Parameter %s of type select requires an options array.', 'basalio' ),
								$param_name
							)
						);
					} else {
						foreach ( $param['options'] as $index => $option ) {
							if ( ! is_array( $option ) || ! isset( $option['value'] ) || ! isset( $option['label'] ) ) {
								$errors->add(
									'invalid_select_option',
									sprintf(
										/* translators: 1: parameter name, 2: option index */
										__( 'Parameter %1$s option %2$s must have value and label.', 'basalio' ),
										$param_name,
										$index
									)
								);
							}
						}
					}
					break;

				case 'range':
					foreach ( array( 'min', 'max', 'step' ) as $field ) {
						if ( ! isset( $param[ $field ] ) ) {
							$errors->add(
								'missing_range_field',
								sprintf(
									/* translators: 1: parameter name, 2: field name */
									__( 'Parameter %1$s of type range requires %2$s.', 'basalio' ),
									$param_name,
									$field
								)
							);
						}
					}
					break;

				case 'toggle':
					// No extra required fields.
					break;

				case 'text':
					// No extra required fields.
					break;
			}

			if ( isset( $param['token'] ) && is_string( $param['token'] ) && '' !== $param['token'] ) {
				if ( ! in_array( $param['token'], $token_allow_list, true ) ) {
					$errors->add(
						'invalid_token',
						sprintf(
							/* translators: 1: parameter name, 2: token name */
							__( 'Parameter %1$s references unknown token: %2$s', 'basalio' ),
							$param_name,
							$param['token']
						)
					);
				}
			}
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Build the token allow-list used by the schema validator.
	 *
	 * Includes every registered token name plus the token families derived from
	 * them (the substring before the last hyphen). A schema parameter can
	 * therefore reference either a specific token (`motion-scroll-threshold`) or
	 * a token family (`duration`, `easing`) whose option values map to individual
	 * tokens.
	 *
	 * A transitional hardcoded list of recognized semantic families is also
	 * included. These families are used by existing module schemas but do not yet
	 * have registered tokens. They are accepted so the validator can pass the
	 * existing schemas without spamming production logs. This list should shrink
	 * as modules register real tokens or are migrated.
	 *
	 * @return string[]
	 */
	private static function get_token_allow_list() {
		$tokens     = Basalio_Tokens::get();
		$allow_list = array_keys( $tokens );

		foreach ( array_keys( $tokens ) as $name ) {
			$family = self::get_token_family( $name );
			if ( '' !== $family && ! in_array( $family, $allow_list, true ) ) {
				$allow_list[] = $family;
			}
		}

		// Transitional families used by existing motion.schema.json files.
		// @todo Remove these as each module registers real tokens or migrates.
		$transitional_families = array(
			'duration',
			'easing',
			'threshold',
			'fallback',
			'percentage',
			'step',
			'focus',
			'announcement',
			'size',
			'lag',
			'blend',
			'axis',
			'delay',
			'strength',
			'distance',
			'viewport',
			'smoothing',
			'count',
			'frame',
			'unit',
			'message',
		);

		foreach ( $transitional_families as $family ) {
			if ( ! in_array( $family, $allow_list, true ) ) {
				$allow_list[] = $family;
			}
		}

		return $allow_list;
	}

	/**
	 * Extract the token family from a full token name.
	 *
	 * The family is everything before the last hyphen. Single-segment names have
	 * no family.
	 *
	 * @param string $token_name Registered token name.
	 * @return string
	 */
	private static function get_token_family( $token_name ) {
		$pos = strrpos( $token_name, '-' );
		if ( false === $pos ) {
			return '';
		}
		return substr( $token_name, 0, $pos );
	}

	/**
	 * Return the default value for a schema parameter, typed for block.json.
	 *
	 * @param array $param Schema parameter definition.
	 * @return mixed
	 */
	private static function get_param_default_value( $param ) {
		$type    = isset( $param['type'] ) ? $param['type'] : '';
		$default = isset( $param['default'] ) ? $param['default'] : null;

		if ( 'toggle' === $type ) {
			return (bool) $default;
		}

		return $default;
	}

	/**
	 * Read a module's motion schema, if one exists.
	 *
	 * @param string $module_dir Module directory.
	 * @return array
	 */
	private function load_block_schema( $module_dir ) {
		$path = $module_dir . '/motion.schema.json';
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$json = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
		return is_array( $json ) ? $json : array();
	}

	/**
	 * Return the list of discovered module names.
	 *
	 * @return string[]
	 */
	public function get_module_names() {
		return $this->module_names;
	}

	/**
	 * Return every free module folder name, whether registered or not.
	 *
	 * Pro uses this to identify free blocks for the disable filter.
	 *
	 * @return string[]
	 */
	public function get_all_module_names() {
		$modules = glob( BASALIO_PATH . 'modules/*/block.json' );

		if ( empty( $modules ) ) {
			return array();
		}

		return array_map(
			function ( $block_json ) {
				return basename( dirname( $block_json ) );
			},
			$modules
		);
	}

	/**
	 * Get the filtered schema for a registered block.
	 *
	 * @param string $block_name Block name, e.g. "basalio/text-reveal".
	 * @return array
	 */
	public static function get_block_schema( $block_name ) {
		return isset( self::$block_schemas[ $block_name ] ) ? self::$block_schemas[ $block_name ] : array();
	}

	/**
	 * Convert a camelCase/snake_case parameter name to a CSS-friendly kebab string.
	 *
	 * @param string $name Parameter name.
	 * @return string
	 */
	private static function param_name_to_kebab( $name ) {
		$name = str_replace( '_', '-', $name );
		$name = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $name );
		$name = preg_replace( '/-+/', '-', $name );
		return trim( strtolower( $name ), '-' );
	}

	/**
	 * Convert a reduced-motion fallback value to a CSS-class-safe slug.
	 *
	 * @param string $value Fallback value.
	 * @return string
	 */
	private static function fallback_value_to_slug( $value ) {
		$value = strtolower( trim( (string) $value ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );
		return trim( $value, '-' );
	}

	/**
	 * Resolve motion parameters to CSS custom properties and fallback classes.
	 *
	 * Reads the block's motion.schema.json and emits one CSS custom property per
	 * parameter, plus any reduced-motion fallback class the CSS expects. This is
	 * the generic front-end half of the schema pipeline: adding a new typed
	 * parameter to motion.schema.json makes it available on the front end without
	 * touching the block's render.php.
	 *
	 * Special cases:
	 *   - Token parameters (duration/easing) resolve to var(--basalio-<token>-<value>).
	 *   - triggerThreshold is converted to a 0-1 fraction on --basalio-motion-scroll-threshold.
	 *   - reducedMotionFallback emits a fallback class rather than a property.
	 *
	 * @param string $block_name   Registered block name, e.g. "basalio/text-reveal".
	 * @param array  $motion_params Saved motionParams attribute value.
	 * @return array { style: string, classes: string[] }
	 */
	public static function resolve_motion_params_to_css( $block_name, $motion_params ) {
		$schema   = self::get_block_schema( $block_name );
		$params   = isset( $schema['parameters'] ) && is_array( $schema['parameters'] ) ? $schema['parameters'] : array();
		$tokens   = Basalio_Tokens::get();
		$styles   = array();
		$classes  = array();
		$block_base = explode( '/', $block_name );
		$block_base = isset( $block_base[1] ) ? $block_base[1] : sanitize_title( $block_name );

		foreach ( $params as $param_name => $param ) {
			if ( ! is_array( $param ) ) {
				continue;
			}

			$type  = isset( $param['type'] ) ? $param['type'] : '';
			$value = isset( $motion_params[ $param_name ] ) ? $motion_params[ $param_name ] : ( isset( $param['default'] ) ? $param['default'] : null );
			$token = isset( $param['token'] ) ? $param['token'] : '';

			// Reduced-motion fallback is exposed as a state class, not a property.
			if ( 'reducedMotionFallback' === $param_name ) {
				$slug = self::fallback_value_to_slug( (string) $value );
				if ( '' !== $slug ) {
					$classes[] = 'basalio-' . $block_base . '--fallback-' . $slug;
				}
				continue;
			}

			// triggerThreshold is a percentage stored as 0-100 but consumed as a fraction.
			if ( 'triggerThreshold' === $param_name ) {
				$threshold = is_numeric( $value ) ? (float) $value : 50;
				$styles[]  = sprintf( '--basalio-motion-scroll-threshold:%s;', max( 0, min( 100, $threshold ) ) / 100 );
				continue;
			}

			// Token parameters map to a CSS variable family, with the token default as fallback.
			if ( '' !== $token ) {
				$token_key = $token . '-' . (string) $value;
				$fallback  = isset( $tokens[ $token_key ] ) ? $tokens[ $token_key ] : ( isset( $param['default'] ) ? $param['default'] : $value );
				$styles[]  = sprintf( '--basalio-tr-%s:var(--basalio-%s, %s);', self::param_name_to_kebab( $param_name ), $token_key, $fallback );
				continue;
			}

			// Range parameters may carry a unit.
			if ( 'range' === $type && isset( $param['unit'] ) && '' !== $param['unit'] ) {
				$styles[] = sprintf( '--basalio-tr-%s:%s%s;', self::param_name_to_kebab( $param_name ), (float) $value, $param['unit'] );
				continue;
			}

			// Toggle as a string boolean so the value is visible in markup.
			if ( 'toggle' === $type ) {
				$styles[] = sprintf( '--basalio-tr-%s:%s;', self::param_name_to_kebab( $param_name ), $value ? 'true' : 'false' );
				continue;
			}

			// Everything else (select, text, plain range) is emitted as-is.
			$styles[] = sprintf( '--basalio-tr-%s:%s;', self::param_name_to_kebab( $param_name ), esc_attr( (string) $value ) );
		}

		return array(
			'style'   => implode( '', $styles ),
			'classes' => $classes,
		);
	}
}
