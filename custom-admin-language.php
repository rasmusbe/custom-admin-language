<?php
/*
Plugin Name: Custom Admin Language
Version: 2.0
*/

class Custom_Admin_Language {
  function __construct() {
    add_filter( 'locale', array( &$this, 'set_language' ), 1 );
  }

  function set_language( $lang ) {
    if ( !is_admin() ||
      ( !empty( $_GET['page'] ) && $_GET['page'] == 'wc-status' ) ||
      ( !empty( $_GET['page'] ) && $_GET['page'] == 'wc-settings' && !empty( $_GET['install_woocommerce_pages'] ) ) ||
      ( !empty( $_SERVER['PHP_SELF'] ) && $_SERVER['PHP_SELF'] == '/wp-admin/options-general.php' ) && empty( $_GET['page'] ) ||
      ( !empty( $_SERVER['PHP_SELF'] ) && $_SERVER['PHP_SELF'] == '/wp-admin/options-permalink.php' ) && empty( $_GET['page'] )
    ) {
      return $lang;
    }

    return defined( 'ADMIN_LANG' ) ? ADMIN_LANG : 'sv_SE';
  }
}
new Custom_Admin_Language;
