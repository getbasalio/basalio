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
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this, 'register_modules' ), 10 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'block_editor_settings_all', array( $this, 'inject_editor_iframe_styles' ) );
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
	 * Load the shared editor styling (and helper script) inside the block editor.
	 * The CSS only targets Basalio's own .basalio-ed-* editor chrome, so loading
	 * it editor-wide is harmless.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// The helper script must load in the editor's main frame, where block
		// registration runs.
		wp_enqueue_script( 'basalio-editor' );
		// The style is also enqueued for the outer frame as a fallback.
		wp_enqueue_style( 'basalio-editor' );
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
	 * @return void
	 */
	public function register_modules() {
		$modules = glob( BASALIO_PATH . 'modules/*/block.json' );

		if ( empty( $modules ) ) {
			return;
		}

		foreach ( $modules as $block_json ) {
			$block_type = register_block_type_from_metadata( dirname( $block_json ) );

			// Point each block's editor script at the plugin's /languages folder so
			// its wp.i18n __() strings resolve once translation files ship. Harmless
			// with no translations installed (it simply finds nothing to load).
			if ( $block_type instanceof WP_Block_Type && function_exists( 'wp_set_script_translations' ) ) {
				foreach ( (array) $block_type->editor_script_handles as $handle ) {
					wp_set_script_translations( $handle, 'basalio', BASALIO_PATH . 'languages' );
				}
			}
		}
	}
}
