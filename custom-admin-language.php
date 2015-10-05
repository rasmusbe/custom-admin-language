<?php

/**
 * Plugin Name: Custom Admin Language
 * Plugin URI: https://github.com/rasmusbe/custom-admin-language
 * Description: Makes it possible to use another language in admin.
 * Author: Rasmus Bengtsson
 * Version: 2.4.0
 */

class Custom_Admin_Language {

	/**
	 * Determine if site (original) or custom language should be returned.
	 *
	 * @var bool
	 */
	protected $return_original = false;

	/**
	 * The constructor. Adds filters to modify language.
	 */
	public function __construct() {
		if ( is_admin() && ! is_network_admin() ) {
			add_filter( 'locale', [ $this, 'set_language' ], 1 );
			add_action( 'admin_init', [ $this, 'site_language_fix' ], 1 );

			add_filter( 'woocommerce_debug_tools' , [ $this, 'woocommerce_language_button' ] );
		}
	}

	/**
	 * Set admin language.
	 * Defaults to sv_SE if ADMIN_LANG is undefined.
	 *
	 * @param string $lang
	 */
	public function set_language( $lang ) {
		if ( $this->return_original || $this->fix_woocommerce() ) {
			return get_option( 'WPLANG', $lang );
		}

		return defined( 'ADMIN_LANG' ) ? ADMIN_LANG : 'sv_SE';
	}

	/**
	 * Fix for WooCommerce so it can handle custom admin language without breaking non-admin pages.
	 * 
	 * @return bool   If true set_language will use site language, otherwise custom language.
	 */
	public function fix_woocommerce() {
		// Check if WooCommerce is active.
		$active_plugins = get_option( 'active_plugins' );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins' ) ) );
		}

		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', $active_plugins ) ) ) {
			return false;
		}

		if ( function_exists( 'is_ajax' ) && is_ajax() ) {
			return true;
		}

		// @codingStandardsIgnoreStart - Ignore rule against processing postdata without nonce
		if ( isset( $_POST['wc_order_action'] ) ) {
			return true;
		}
		// @codingStandardsIgnoreEnd

		// Do not translate WooCommerce when creating pages or upgrade langs
		if (
			( ! empty( $_GET['page'] ) && $_GET['page'] == 'wc-settings' && ! empty( $_GET['install_woocommerce_pages'] ) ) ||
			( ! empty( $_GET['page'] ) && $_GET['page'] == 'wc-status' && ! empty( $_GET['action'] ) && $_GET['action'] == 'install_pages' ) ||
			( ! empty( $_GET['page'] ) && $_GET['page'] == 'wc-status' && ! empty( $_GET['action'] ) && $_GET['action'] == 'translation_upgrade' )
		) {
			return true;
		}

		// Set WooCommerce permalinks to site language if not set. Will otherwise break permalinks.
		$this->return_original = true;
		$permalinks = get_option( 'woocommerce_permalinks' );

		if ( empty( $permalinks ) ) {
			$permalinks = [];
		}

		$was_loaded = false;

		if ( empty( $permalinks['category_base'] ) || empty( $permalinks['tag_base'] ) ) {

			if ( is_textdomain_loaded( 'woocommerce' ) ) {
				$was_loaded = true;
			}

			load_plugin_textdomain( 'woocommerce', false, WP_PLUGIN_DIR . '/woocommerce/i18n/languages' );

			if ( empty( $permalinks['category_base'] ) ) {
				$permalinks['category_base'] = _x( 'product-category', 'slug', 'woocommerce' );
			}

			if ( empty( $permalinks['tag_base'] ) ) {
				$permalinks['tag_base'] = _x( 'product-tag', 'slug', 'woocommerce' );
			}

			update_option( 'woocommerce_permalinks', $permalinks );

			unload_textdomain( 'woocommerce' );

		}

		$this->return_original = false;

		// Reload WooCommerce textdomain
		if ( $was_loaded ) {
			load_plugin_textdomain( 'woocommerce', false, WP_PLUGIN_DIR . '/woocommerce/i18n/languages' );
		}
	}

	/**
	 * Add settings fields to fix get_locale in general settings.
	 * Quite ugly fix, but WP have no actions or filters to use here.
	 */
	public function site_language_fix() {
		add_settings_field( 'site_language_fix_1', '', [ $this, 'site_language_fix_1' ], 'general', 'default' );
		add_settings_section( 'site_language_fix_2', '', [ $this, 'site_language_fix_2' ], 'general' );
	}

	/**
	 * Site language fix 1. Runs before language dropdown.
	 * Sets the `return_original` to true.
	 */
	public function site_language_fix_1() {
		$this->return_original = true;
		load_default_textdomain( get_locale() );
	}

	/**
	 * Site language fix 2. Runs after language dropdown.
	 * Sets the `return_original` to false.
	 */
	public function site_language_fix_2() {
		$this->return_original = false;
		load_default_textdomain( get_locale() );
	}

	/**
	 * Fix WooCommerce language buttons if custom language is english.
	 *
	 * @param  array $tools
	 *
	 * @return array
	 */
	public function woocommerce_language_button( array $tools ) {
		if ( ! isset( $tools['translation_upgrade'] ) ) {
			$tools['translation_upgrade'] = [
				'name'    => __( 'Translation Upgrade', 'woocommerce' ),
				'button'  => __( 'Force Translation Upgrade', 'woocommerce' ),
				'desc'    => __( '<strong class="red">Note:</strong> This option will force the translation upgrade for your language if a translation is available.', 'woocommerce' ),
			];
		}

		return $tools;
	}

}

new Custom_Admin_Language;
