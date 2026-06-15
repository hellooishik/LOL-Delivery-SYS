<?php
/**
 * LOL Delivery System functions and definitions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define theme constants
define( 'LOL_THEME_DIR', get_template_directory() );
define( 'LOL_THEME_URI', get_template_directory_uri() );
define( 'LOL_THEME_VERSION', '1.0.0' );

// Include Composer autoloader if it exists (for PhpSpreadsheet)
if ( file_exists( LOL_THEME_DIR . '/vendor/autoload.php' ) ) {
    require_once LOL_THEME_DIR . '/vendor/autoload.php';
}

// Include required files
require_once LOL_THEME_DIR . '/inc/database.php';
require_once LOL_THEME_DIR . '/inc/roles.php';
require_once LOL_THEME_DIR . '/inc/admin-menu.php';
require_once LOL_THEME_DIR . '/inc/ajax-handlers.php';
require_once LOL_THEME_DIR . '/inc/export-excel.php';

/**
 * Enqueue scripts and styles.
 */
function lol_delivery_scripts() {
    // Enqueue frontend CSS
    wp_enqueue_style( 'lol-delivery-style', LOL_THEME_URI . '/assets/css/app.css', array(), LOL_THEME_VERSION );

    // Enqueue frontend JS
    wp_enqueue_script( 'lol-delivery-script', LOL_THEME_URI . '/assets/js/app.js', array('jquery'), LOL_THEME_VERSION, true );

    // Localize script for AJAX
    wp_localize_script( 'lol-delivery-script', 'lol_ajax_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'lol_delivery_nonce' )
    ) );
}
add_action( 'wp_enqueue_scripts', 'lol_delivery_scripts' );

/**
 * Redirect delivery partners away from wp-admin to frontend
 */
function lol_redirect_delivery_partner() {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
        $current_user = wp_get_current_user();
        if ( in_array( 'delivery_partner', (array) $current_user->roles ) && ! current_user_can( 'manage_options' ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }
}
add_action( 'admin_init', 'lol_redirect_delivery_partner' );

/**
 * Hide admin bar for delivery partners
 */
function lol_hide_admin_bar() {
    $current_user = wp_get_current_user();
    if ( in_array( 'delivery_partner', (array) $current_user->roles ) && ! current_user_can( 'manage_options' ) ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'lol_hide_admin_bar' );

/**
 * Theme activation hook workaround
 * Themes don't have a direct activation hook like plugins. 
 * We use after_switch_theme.
 */
function lol_theme_activation() {
    lol_create_custom_tables();
    lol_add_delivery_partner_role();
}
add_action('after_switch_theme', 'lol_theme_activation');
