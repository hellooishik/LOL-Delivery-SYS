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
define( 'LOL_THEME_VERSION', time() );

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
    // Enqueue Google Fonts
    wp_enqueue_style( 'lol-google-fonts', 'https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap', false );

    // Enqueue frontend CSS
    wp_enqueue_style( 'lol-delivery-style', LOL_THEME_URI . '/assets/css/app.css', array('lol-google-fonts'), LOL_THEME_VERSION );

    // Enqueue SheetJS
    wp_enqueue_script( 'sheetjs', 'https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js', array(), null, true );

    // Enqueue frontend JS
    wp_enqueue_script( 'lol-delivery-script', LOL_THEME_URI . '/assets/js/app.js', array('jquery', 'sheetjs'), LOL_THEME_VERSION, true );

    // Localize script for AJAX
    wp_localize_script( 'lol-delivery-script', 'lol_ajax_obj', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'lol_delivery_nonce' ),
        'excel_url' => LOL_THEME_URI . '/Laugh-O-Laundry  Customer Sheet .xlsx'
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

/**
 * Ensure delivery_boy column is updated to varchar
 * Also add new columns for pickup_agent_name, payment_mode, total_bill_amount, balance_due
 */
function lol_update_database_schema() {
    global $wpdb;
    $table_orders = $wpdb->prefix . 'laundry_orders';
    // Suppress errors if table doesn't exist yet
    $wpdb->suppress_errors = true;
    
    $table_items = $wpdb->prefix . 'laundry_order_items';
    
    // Ensure delivery_boy is varchar
    $wpdb->query("ALTER TABLE $table_orders MODIFY delivery_boy VARCHAR(255) NULL");
    
    // Add new columns if they don't exist
    $wpdb->query("ALTER TABLE $table_orders ADD COLUMN pickup_agent_name VARCHAR(255) NULL");
    $wpdb->query("ALTER TABLE $table_orders ADD COLUMN payment_mode VARCHAR(50) NULL");
    $wpdb->query("ALTER TABLE $table_orders ADD COLUMN total_bill_amount DECIMAL(10,2) NULL");
    $wpdb->query("ALTER TABLE $table_orders ADD COLUMN balance_due DECIMAL(10,2) NULL");
    
    $wpdb->query("ALTER TABLE $table_items ADD COLUMN delivered_quantity INT(11) DEFAULT 0 NOT NULL");

    $wpdb->suppress_errors = false;
}
add_action('init', 'lol_update_database_schema');
