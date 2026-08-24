<?php
/**
 * Basalio wp-admin dashboard.
 *
 * Registers the top-level Basalio menu and renders the free dashboard.
 * Pro extends this surface via the `basalio_admin_menu` action.
 *
 * @package Basalio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Basalio_Admin
 */
class Basalio_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var Basalio_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get (and lazily create) the singleton.
	 *
	 * @return Basalio_Admin
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
		add_action( 'admin_menu', array( $this, 'register_page' ) );
	}

	/**
	 * Register the top-level Basalio menu.
	 *
	 * @return void
	 */
	public function register_page() {
		add_menu_page(
			__( 'Basalio', 'basalio' ),
			__( 'Basalio', 'basalio' ),
			'edit_posts',
			'basalio',
			array( $this, 'render_dashboard' ),
			'dashicons-admin-customizer',
			58
		);

		/**
		 * Fires after the free Basalio admin menu is registered.
		 *
		 * Pro uses this to add submenus and override the parent page.
		 */
		do_action( 'basalio_admin_menu' );
	}

	/**
	 * Render the free dashboard.
	 *
	 * @return void
	 */
	public function render_dashboard() {
		$tabs = apply_filters( 'basalio_admin_tabs', array() );

		echo '<div class="wrap" id="basalio-admin">';
		echo '<h1>' . esc_html__( 'Basalio', 'basalio' ) . '</h1>';

		if ( ! empty( $tabs ) ) {
			echo '<nav class="nav-tab-wrapper" aria-label="Basalio sections">';
			foreach ( $tabs as $slug => $tab ) {
				$label = isset( $tab['label'] ) ? $tab['label'] : $slug;
				$url   = isset( $tab['url'] ) ? $tab['url'] : '#';
				echo '<a href="' . esc_url( $url ) . '" class="nav-tab">' . esc_html( $label ) . '</a>';
			}
			echo '</nav>';
		}

		$this->render_free_dashboard_content();

		echo '</div>';
	}

	/**
	 * Render the default free-plugin dashboard content.
	 *
	 * @return void
	 */
	private function render_free_dashboard_content() {
		$modules = class_exists( 'Basalio' ) ? Basalio::instance()->get_module_names() : array();

		echo '<div class="basalio-free-dashboard" style="margin-top:20px;">';
		echo '<p>' . esc_html__( 'Basalio blocks active on this site:', 'basalio' ) . '</p>';

		if ( empty( $modules ) ) {
			echo '<p>' . esc_html__( 'No Basalio blocks are active.', 'basalio' ) . '</p>';
		} else {
			echo '<ul>';
			foreach ( $modules as $name ) {
				echo '<li>' . esc_html( ucwords( str_replace( array( '-', '_' ), ' ', $name ) ) ) . '</li>';
			}
			echo '</ul>';
		}

		echo '<div class="basalio-upgrade-row" style="margin-top:24px;padding:16px;border:1px solid #c3c4c7;background:#fff;">';
		echo '<p><strong>' . esc_html__( 'Basalio Pro', 'basalio' ) . '</strong></p>';
		echo '<p>' . esc_html__( 'Unlock design tokens, patterns, and compliance tools with Basalio Pro.', 'basalio' ) . '</p>';
		echo '<a class="button" href="https://basalio.com/pro" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Explore Basalio Pro', 'basalio' ) . '</a>';
		echo '</div>';

		echo '</div>';
	}
}
