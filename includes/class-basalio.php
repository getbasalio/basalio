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
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( $this, 'register_modules' ), 10 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
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
			array(),
			BASALIO_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
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
		wp_enqueue_script( 'basalio-editor' );
		wp_enqueue_style( 'basalio-editor' );
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
			register_block_type_from_metadata( dirname( $block_json ) );
		}
	}
}
