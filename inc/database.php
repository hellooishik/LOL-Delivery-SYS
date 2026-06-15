<?php
/**
 * Database table creation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lol_create_custom_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table_orders = $wpdb->prefix . 'laundry_orders';
    $table_items = $wpdb->prefix . 'laundry_order_items';

    $sql_orders = "CREATE TABLE $table_orders (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        token_id varchar(50) NOT NULL,
        customer_name varchar(255) NOT NULL,
        phone_number varchar(20) NOT NULL,
        pickup_date date NOT NULL,
        delivery_date date NULL,
        delivery_boy varchar(255) NULL,
        payment_status varchar(20) DEFAULT 'Unpaid' NOT NULL,
        amount_received decimal(10,2) NULL,
        order_status varchar(50) DEFAULT 'Pickup Completed' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY token_id (token_id)
    ) $charset_collate;";

    $sql_items = "CREATE TABLE $table_items (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        quantity int(11) NOT NULL,
        service_type varchar(100) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY order_id (order_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_orders );
    dbDelta( $sql_items );
}
