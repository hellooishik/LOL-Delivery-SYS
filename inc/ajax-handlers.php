<?php
/**
 * AJAX Handlers
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Save Pickup
add_action( 'wp_ajax_lol_save_pickup', 'lol_ajax_save_pickup' );
add_action( 'wp_ajax_nopriv_lol_save_pickup', 'lol_ajax_save_pickup' );

function lol_ajax_save_pickup() {
    check_ajax_referer( 'lol_delivery_nonce', 'nonce' );

    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';
    $items_table = $wpdb->prefix . 'laundry_order_items';

    $customer_name = sanitize_text_field( $_POST['customer_name'] );
    $phone_number = sanitize_text_field( $_POST['phone_number'] );
    $pickup_date = current_time('Y-m-d');
    
    $items = isset($_POST['items']) ? $_POST['items'] : array();

    if ( empty($customer_name) || empty($phone_number) || empty($items) ) {
        wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
    }

    // Generate Token ID
    // Format: LOL-YYYYMMDD-XXXX
    $date_prefix = 'LOL-' . current_time('Ymd') . '-';
    
    // Find the latest token for today
    $latest_token = $wpdb->get_var($wpdb->prepare(
        "SELECT token_id FROM $orders_table WHERE token_id LIKE %s ORDER BY id DESC LIMIT 1",
        $date_prefix . '%'
    ));

    if ( $latest_token ) {
        $last_seq = intval( substr($latest_token, -4) );
        $new_seq = str_pad( $last_seq + 1, 4, '0', STR_PAD_LEFT );
    } else {
        $new_seq = '0001';
    }

    $token_id = $date_prefix . $new_seq;

    // Insert Order
    $inserted = $wpdb->insert(
        $orders_table,
        array(
            'token_id' => $token_id,
            'customer_name' => $customer_name,
            'phone_number' => $phone_number,
            'pickup_date' => $pickup_date,
            'order_status' => 'Processing'
        ),
        array('%s', '%s', '%s', '%s', '%s')
    );

    if ( $inserted ) {
        $order_id = $wpdb->insert_id;

        // Insert Items
        foreach ( $items as $item ) {
            $wpdb->insert(
                $items_table,
                array(
                    'order_id' => $order_id,
                    'quantity' => intval( $item['quantity'] ),
                    'service_type' => sanitize_text_field( $item['service_type'] )
                ),
                array('%d', '%d', '%s')
            );
        }

        wp_send_json_success( array( 
            'token_id' => $token_id,
            'customer_name' => $customer_name,
            'phone_number' => $phone_number,
            'pickup_date' => date_i18n( get_option( 'date_format' ), strtotime( $pickup_date ) ),
            'items' => $items
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to save order.' ) );
    }
}

// Search Token
add_action( 'wp_ajax_lol_search_token', 'lol_ajax_search_token' );
add_action( 'wp_ajax_nopriv_lol_search_token', 'lol_ajax_search_token' );

function lol_ajax_search_token() {
    check_ajax_referer( 'lol_delivery_nonce', 'nonce' );

    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';
    $items_table = $wpdb->prefix . 'laundry_order_items';

    $token_id = sanitize_text_field( $_POST['token_id'] );

    if ( empty($token_id) ) {
        wp_send_json_error( array( 'message' => 'Token ID is required.' ) );
    }

    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $orders_table WHERE token_id = %s",
        $token_id
    ));

    if ( $order ) {
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $items_table WHERE order_id = %d",
            $order->id
        ));

        // Format dates
        $order->pickup_date = date_i18n( get_option( 'date_format' ), strtotime( $order->pickup_date ) );
        
        wp_send_json_success( array(
            'order' => $order,
            'items' => $items
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Token ID not found.' ) );
    }
}

// Save Delivery
add_action( 'wp_ajax_lol_save_delivery', 'lol_ajax_save_delivery' );
add_action( 'wp_ajax_nopriv_lol_save_delivery', 'lol_ajax_save_delivery' );

function lol_ajax_save_delivery() {
    check_ajax_referer( 'lol_delivery_nonce', 'nonce' );

    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';

    $token_id = sanitize_text_field( $_POST['token_id'] );
    $delivery_boy_name = sanitize_text_field( $_POST['delivery_boy'] );
    $payment_status = sanitize_text_field( $_POST['payment_status'] );
    $amount_received = isset($_POST['amount_received']) ? floatval( $_POST['amount_received'] ) : 0;
    
    $delivery_date = current_time('Y-m-d');

    if ( empty($token_id) || empty($delivery_boy_name) ) {
        wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
    }

    $updated = $wpdb->update(
        $orders_table,
        array(
            'delivery_date' => $delivery_date,
            'delivery_boy' => $delivery_boy_name,
            'payment_status' => $payment_status,
            'amount_received' => $amount_received,
            'order_status' => 'Delivered'
        ),
        array( 'token_id' => $token_id ),
        array('%s', '%s', '%s', '%f', '%s'),
        array('%s')
    );

    if ( $updated !== false ) {
        wp_send_json_success( array( 'message' => 'Delivery saved successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to update order.' ) );
    }
}
