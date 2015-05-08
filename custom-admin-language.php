<?php
/*
Plugin Name: Custom Admin Language
Author: Rasmus Bengtsson
Version: 2.2.0
*/

class Custom_Admin_Language {
	var $return_original = FALSE;

	function __construct() {
		if ( is_admin() && ! is_network_admin() ) {
			add_filter( 'locale', array( $this, 'set_language' ), 1 );
			add_action( 'admin_init', array( $this, 'site_language_fix' ), 1 );

			add_filter( 'woocommerce_debug_tools' , array( $this, 'woocommerce_language_button' ) );
		}
	}

	function set_language( $lang ) {
		global $locale;
		if ( $this->return_original || $this->fix_woocommerce() ) {
			$lang = get_option( 'WPLANG', $lang );
			$locale = $lang;
			return $lang;
		}

		$lang = defined( 'ADMIN_LANG' ) ? ADMIN_LANG : 'sv_SE';
		$locale = $lang;
		return $lang;
	}

	function fix_woocommerce() {
		/**
		 * Check if WooCommerce is active
		 * */
		$active_plugins = get_option( 'active_plugins' );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins' ) ) );
		}

		if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', $active_plugins ) ) ) {
			return FALSE;
		}

		if ( function_exists( 'is_ajax' ) && is_ajax() ) {
			return TRUE;
		}

		// Do not translate WooCommerce when creating pages or upgrade langs
		if (
			( !empty( $_GET['page'] ) && $_GET['page'] == 'wc-settings' && !empty( $_GET['install_woocommerce_pages'] ) ) ||
			( !empty( $_GET['page'] ) && $_GET['page'] == 'wc-status' && !empty( $_GET['action'] && $_GET['action'] == 'install_pages' ) ) ||
			( !empty( $_GET['page'] ) && $_GET['page'] == 'wc-status' && !empty( $_GET['action'] && $_GET['action'] == 'translation_upgrade' ) )
		) {
			return TRUE;
		}

		// Set WooCommerce permalinks if not set
		$this->return_original = TRUE;
		$permalinks = get_option( 'woocommerce_permalinks' );

		if ( empty( $permalinks ) ) {
			$permalinks = array();
		}

		$was_loaded = FALSE;

		if ( empty( $permalinks['category_base'] ) || empty( $permalinks['tag_base'] ) ) {

			if ( is_textdomain_loaded( 'woocommerce' ) ) {
				$was_loaded = TRUE;
			}

			load_plugin_textdomain( 'woocommerce', false, WP_PLUGIN_DIR . "/woocommerce/i18n/languages" );

			if ( empty( $permalinks['category_base'] ) ) {
				$permalinks['category_base'] = _x( 'product-category', 'slug', 'woocommerce' );
			}

			if ( empty( $permalinks['tag_base'] ) ) {
				$permalinks['tag_base'] = _x( 'product-tag', 'slug', 'woocommerce' );
			}

			update_option( 'woocommerce_permalinks', $permalinks );

			unload_textdomain( 'woocommerce' );

		}

		$this->return_original = FALSE;

		if ( $was_loaded ) {
			load_plugin_textdomain( 'woocommerce', false, WP_PLUGIN_DIR . "/woocommerce/i18n/languages" );
		}
	}

	function site_language_fix() {
		add_settings_field( 'site_language_fix_1', '', array( $this, 'site_language_fix_1' ), 'general', 'default' );
		add_settings_section( 'site_language_fix_2', '', array( $this, 'site_language_fix_2' ), 'general' );
	}

	function site_language_fix_1() {
		$this->return_original = TRUE;
		load_default_textdomain( get_locale() );
	}

	function site_language_fix_2() {
		$this->return_original = FALSE;
		load_default_textdomain( get_locale() );
	}

	function woocommerce_language_button( $tools ) {
		if ( !isset( $tools['translation_upgrade'] ) ) {
			$tools['translation_upgrade'] = array(
				'name'    => __( 'Translation Upgrade', 'woocommerce' ),
				'button'  => __( 'Force Translation Upgrade', 'woocommerce' ),
				'desc'    => __( '<strong class="red">Note:</strong> This option will force the translation upgrade for your language if a translation is available.', 'woocommerce' ),
			);
		}
		return $tools;
	}
}
new Custom_Admin_Language;
