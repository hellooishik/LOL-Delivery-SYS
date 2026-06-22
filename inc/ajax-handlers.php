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
    $address = isset($_POST['address']) ? sanitize_textarea_field( $_POST['address'] ) : '';
    $pickup_date = current_time('Y-m-d');
    
    $items = isset($_POST['items']) ? $_POST['items'] : array();

    $pickup_agent_name = isset($_POST['pickup_agent_name']) ? sanitize_text_field( $_POST['pickup_agent_name'] ) : '';

    if ( empty($customer_name) || empty($phone_number) || empty($items) || empty($address) ) {
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
            'address' => $address,
            'pickup_date' => $pickup_date,
            'pickup_agent_name' => $pickup_agent_name,
            'order_status' => 'Picked Up'
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
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

    $search_term = sanitize_text_field( $_POST['token_id'] );

    if ( empty($search_term) ) {
        wp_send_json_error( array( 'message' => 'Search term is required.' ) );
    }

    if ( strlen($search_term) === 4 && is_numeric($search_term) ) {
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE token_id LIKE %s ORDER BY id DESC LIMIT 1",
            '%-' . $search_term
        ));
    } else {
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE token_id = %s",
            $search_term
        ));
    }

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
        wp_send_json_error( array( 'message' => 'Order not found.' ) );
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
    $delivery_type = isset($_POST['delivery_type']) ? sanitize_text_field( $_POST['delivery_type'] ) : 'Full';
    $payment_mode = isset($_POST['payment_mode']) ? sanitize_text_field( $_POST['payment_mode'] ) : '';
    $total_bill_amount = isset($_POST['total_bill_amount']) ? floatval( $_POST['total_bill_amount'] ) : 0;
    $amount_received = isset($_POST['amount_received']) ? floatval( $_POST['amount_received'] ) : 0;
    $balance_due = isset($_POST['balance_due']) ? floatval( $_POST['balance_due'] ) : 0;
    
    $delivery_date = current_time('Y-m-d');

    if ( empty($token_id) || empty($delivery_boy_name) ) {
        wp_send_json_error( array( 'message' => 'Missing required fields.' ) );
    }

    $order_status = ($delivery_type === 'Partial') ? 'Partial Delivery' : 'Delivered';

    $updated = $wpdb->update(
        $orders_table,
        array(
            'delivery_date' => $delivery_date,
            'delivery_boy' => $delivery_boy_name,
            'payment_status' => $payment_status,
            'payment_mode' => $payment_mode,
            'total_bill_amount' => $total_bill_amount,
            'amount_received' => $amount_received,
            'balance_due' => $balance_due,
            'order_status' => $order_status
        ),
        array( 'token_id' => $token_id ),
        array('%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s'),
        array('%s')
    );

    if ( $updated !== false ) {
        // Update delivered quantities
        $items_table = $wpdb->prefix . 'laundry_order_items';
        $delivered_items = isset($_POST['delivered_items']) ? $_POST['delivered_items'] : array();
        foreach ( $delivered_items as $item_id => $qty ) {
            $wpdb->update(
                $items_table,
                array( 'delivered_quantity' => intval($qty) ),
                array( 'id' => intval($item_id) ),
                array( '%d' ),
                array( '%d' )
            );
        }

        wp_send_json_success( array( 'message' => 'Delivery saved successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to update order.' ) );
    }
}

// Save Excel File
add_action( 'wp_ajax_lol_save_excel_file', 'lol_ajax_save_excel_file' );

function lol_ajax_save_excel_file() {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    $base64 = isset($_POST['excel_base64']) ? $_POST['excel_base64'] : '';
    if ( empty($base64) ) {
        wp_send_json_error( array( 'message' => 'No excel data provided.' ) );
    }

    $decoded = base64_decode($base64);
    if ( $decoded === false ) {
        wp_send_json_error( array( 'message' => 'Failed to decode excel data.' ) );
    }

    $file_path = LOL_THEME_DIR . '/Laugh-O-Laundry  Customer Sheet .xlsx';
    
    $saved = file_put_contents($file_path, $decoded);

    if ( $saved !== false ) {
        wp_send_json_success( array( 'message' => 'Excel file updated successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to write file to disk. Check permissions.' ) );
    }
}

// Get Order for Edit
add_action( 'wp_ajax_lol_get_order_for_edit', 'lol_ajax_get_order_for_edit' );
add_action( 'wp_ajax_nopriv_lol_get_order_for_edit', 'lol_ajax_get_order_for_edit' );

function lol_ajax_get_order_for_edit() {
    check_ajax_referer( 'lol_delivery_nonce', 'nonce' );

    $password = sanitize_text_field( $_POST['password'] );
    if ( $password !== 'admin123' ) {
        wp_send_json_error( array( 'message' => 'Incorrect password.' ) );
    }

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

        wp_send_json_success( array(
            'order' => $order,
            'items' => $items
        ) );
    } else {
        wp_send_json_error( array( 'message' => 'Token ID not found.' ) );
    }
}

// Save Edited Order
add_action( 'wp_ajax_lol_save_edited_order', 'lol_ajax_save_edited_order' );
add_action( 'wp_ajax_nopriv_lol_save_edited_order', 'lol_ajax_save_edited_order' );

function lol_ajax_save_edited_order() {
    check_ajax_referer( 'lol_delivery_nonce', 'nonce' );

    $password = sanitize_text_field( $_POST['password'] );
    if ( $password !== 'admin123' ) {
        wp_send_json_error( array( 'message' => 'Incorrect password.' ) );
    }

    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';
    $items_table = $wpdb->prefix . 'laundry_order_items';

    $token_id = sanitize_text_field( $_POST['token_id'] );
    $items = isset($_POST['items']) ? $_POST['items'] : array();

    if ( empty($token_id) || empty($items) ) {
        wp_send_json_error( array( 'message' => 'Missing required fields or items.' ) );
    }

    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT id FROM $orders_table WHERE token_id = %s",
        $token_id
    ));

    if ( $order ) {
        // Delete existing items
        $wpdb->delete( $items_table, array( 'order_id' => $order->id ), array( '%d' ) );

        // Insert new items
        foreach ( $items as $item ) {
            $wpdb->insert(
                $items_table,
                array(
                    'order_id' => $order->id,
                    'quantity' => intval( $item['quantity'] ),
                    'service_type' => sanitize_text_field( $item['service_type'] )
                ),
                array('%d', '%d', '%s')
            );
        }

        wp_send_json_success( array( 'message' => 'Order updated successfully.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Order not found.' ) );
    }
}

// Sync Delivery Dates from Excel to DB
add_action( 'wp_ajax_lol_sync_delivery_dates', 'lol_ajax_sync_delivery_dates' );

function lol_ajax_sync_delivery_dates() {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';

    $updates_json = isset($_POST['updates']) ? stripslashes($_POST['updates']) : '';
    $updates = json_decode($updates_json, true);

    if ( empty($updates) || ! is_array($updates) ) {
        wp_send_json_error( array( 'message' => 'No updates provided.' ) );
    }

    $synced = 0;
    foreach ($updates as $update) {
        $token_id = sanitize_text_field($update['token_id']);
        $delivery_date_raw = sanitize_text_field($update['delivery_date']);

        if ( empty($token_id) || empty($delivery_date_raw) ) continue;

        // Try to parse the delivery date into Y-m-d format
        $timestamp = strtotime($delivery_date_raw);
        if ( $timestamp === false ) continue;
        $delivery_date = date('Y-m-d', $timestamp);

        $updated = $wpdb->update(
            $orders_table,
            array( 'delivery_date' => $delivery_date ),
            array( 'token_id' => $token_id ),
            array( '%s' ),
            array( '%s' )
        );

        if ( $updated !== false ) {
            $synced++;
        }
    }

    wp_send_json_success( array( 'synced' => $synced ) );
}

// Log WhatsApp Message
add_action( 'wp_ajax_lol_log_whatsapp', 'lol_ajax_log_whatsapp' );
add_action( 'wp_ajax_nopriv_lol_log_whatsapp', 'lol_ajax_log_whatsapp' );

function lol_ajax_log_whatsapp() {
    check_ajax_referer( 'lol_delivery_nonce', 'nonce' );

    global $wpdb;
    $logs_table = $wpdb->prefix . 'laundry_whatsapp_logs';

    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $phone_number = sanitize_text_field( $_POST['phone_number'] );
    $message = sanitize_textarea_field( $_POST['message'] );

    if ( empty($phone_number) || empty($message) ) {
        wp_send_json_error( array( 'message' => 'Missing details' ) );
    }

    $wpdb->insert(
        $logs_table,
        array(
            'order_id' => $order_id,
            'phone_number' => $phone_number,
            'message' => $message,
            'status' => 'Sent via wa.me'
        ),
        array('%d', '%s', '%s', '%s')
    );

    wp_send_json_success();
}

// Update Order Status manually
add_action( 'wp_ajax_lol_update_order_status', 'lol_ajax_update_order_status' );

function lol_ajax_update_order_status() {
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ) );
    }

    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';

    $token_id = sanitize_text_field( $_POST['token_id'] );
    $new_status = sanitize_text_field( $_POST['new_status'] );

    if ( empty($token_id) || empty($new_status) ) {
        wp_send_json_error( array( 'message' => 'Missing parameters.' ) );
    }

    $updated = $wpdb->update(
        $orders_table,
        array( 'order_status' => $new_status ),
        array( 'token_id' => $token_id ),
        array( '%s' ),
        array( '%s' )
    );

    if ( $updated !== false ) {
        wp_send_json_success( array( 'message' => 'Status updated.' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Failed to update.' ) );
    }
}
