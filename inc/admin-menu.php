<?php
/**
 * Admin Menus and Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function lol_admin_menu() {
    add_menu_page(
        'Laundry Management',
        'Laundry Management',
        'manage_options',
        'lol-laundry-management',
        'lol_admin_dashboard_page',
        'dashicons-cart',
        30
    );

    add_submenu_page(
        'lol-laundry-management',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'lol-laundry-management',
        'lol_admin_dashboard_page'
    );

    add_submenu_page(
        'lol-laundry-management',
        'Orders',
        'Orders',
        'manage_options',
        'lol-orders',
        'lol_admin_orders_page'
    );

    add_submenu_page(
        'lol-laundry-management',
        "Today's Delivery",
        "🚚 Today's Delivery",
        'manage_options',
        'lol-todays-delivery',
        'lol_admin_todays_delivery_page'
    );

    add_submenu_page(
        'lol-laundry-management',
        'Export Excel',
        'Export Excel',
        'manage_options',
        'lol-export',
        'lol_admin_export_page'
    );

    add_submenu_page(
        'lol-laundry-management',
        'Main Excel Sheet',
        'Main Excel Sheet',
        'manage_options',
        'lol-main-excel',
        'lol_admin_main_excel_page'
    );
}
add_action( 'admin_menu', 'lol_admin_menu' );

/**
 * Helper: Generate WhatsApp deep link
 */
function lol_whatsapp_link( $phone, $message ) {
    // Strip non-numeric, add 91 country code if not present
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if ( strlen($phone) === 10 ) {
        $phone = '91' . $phone;
    }
    return 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
}

/**
 * Helper: Payment status badge HTML
 */
function lol_payment_badge( $status ) {
    $status = strtolower( trim($status) );
    switch ($status) {
        case 'paid':
            return '<span class="lol-badge lol-badge-paid">Paid</span>';
        case 'partial':
            return '<span class="lol-badge lol-badge-partial">Partial</span>';
        case 'unpaid':
        default:
            return '<span class="lol-badge lol-badge-unpaid">Unpaid</span>';
    }
}

/**
 * Helper: Order status badge HTML
 */
function lol_status_badge( $status ) {
    $s = strtolower( trim($status) );
    if ( $s === 'delivered' ) {
        return '<span class="lol-badge lol-badge-delivered">Delivered</span>';
    } elseif ( $s === 'processing' ) {
        return '<span class="lol-badge lol-badge-processing">Processing</span>';
    }
    return '<span class="lol-badge lol-badge-processing">' . esc_html($status) . '</span>';
}

/**
 * Admin page styles (shared across admin pages)
 */
function lol_admin_page_styles() {
    ?>
    <style>
        .lol-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            line-height: 1.4;
        }
        .lol-badge-paid {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .lol-badge-unpaid {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .lol-badge-partial {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .lol-badge-delivered {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .lol-badge-processing {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .lol-date-header td {
            background: #f0f6fc !important;
            font-weight: 700;
            font-size: 14px;
            color: #1d4ed8;
            padding: 10px 15px !important;
            border-top: 2px solid #93c5fd;
            border-bottom: 1px solid #bfdbfe;
        }
        .lol-wa-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            background: #25D366;
            color: #fff !important;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.2s;
            white-space: nowrap;
        }
        .lol-wa-btn:hover {
            background: #1ea952;
            color: #fff !important;
        }
        .lol-wa-btn-small {
            padding: 3px 8px;
            font-size: 11px;
        }
        .lol-items-detail {
            font-size: 12px;
            color: #555;
            line-height: 1.6;
        }
        .lol-items-detail .delivered-ok {
            color: #166534;
        }
        .lol-items-detail .delivered-partial {
            color: #92400e;
        }
        .lol-amount-col {
            white-space: nowrap;
        }
        .lol-today-banner {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #78350f;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        .lol-today-banner .count {
            background: #fff;
            color: #d97706;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 14px;
        }
        .lol-stat-cards {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .lol-stat-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            flex: 1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .lol-stat-card h3 {
            margin: 0 0 8px 0;
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .lol-stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
        }
    </style>
    <?php
}

function lol_admin_dashboard_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';

    $today = current_time('Y-m-d');
    
    $today_pickups = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE pickup_date = '$today'");
    $today_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE delivery_date = '$today'");
    $pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE order_status != 'Delivered'");

    lol_admin_page_styles();
    ?>
    <div class="wrap">
        <h1>Laundry Management Dashboard</h1>
        
        <div class="lol-stat-cards">
            <div class="lol-stat-card">
                <h3>Today's Pickups</h3>
                <p class="stat-value"><?php echo intval($today_pickups); ?></p>
            </div>
            <div class="lol-stat-card">
                <h3>Today's Deliveries</h3>
                <p class="stat-value"><?php echo intval($today_deliveries); ?></p>
            </div>
            <div class="lol-stat-card">
                <h3>Pending Orders</h3>
                <p class="stat-value"><?php echo intval($pending_orders); ?></p>
            </div>
        </div>
    </div>
    <?php
}

function lol_admin_orders_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';
    $items_table = $wpdb->prefix . 'laundry_order_items';

    // Fetch all orders grouped by pickup_date DESC
    $orders = $wpdb->get_results("SELECT * FROM $orders_table ORDER BY pickup_date DESC, id DESC LIMIT 200");

    // Pre-fetch all items for these orders in one query
    $order_ids = array_map(function($o) { return intval($o->id); }, $orders);
    $all_items = array();
    if ( ! empty($order_ids) ) {
        $ids_str = implode(',', $order_ids);
        $items_rows = $wpdb->get_results("SELECT * FROM $items_table WHERE order_id IN ($ids_str)");
        foreach ($items_rows as $item) {
            $all_items[$item->order_id][] = $item;
        }
    }

    lol_admin_page_styles();
    ?>
    <div class="wrap">
        <h1>All Orders</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 140px;">Token ID</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Pickup Date</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                    <th>Payment Amount</th>
                    <th>Items Delivered</th>
                    <th>Delivery Boy</th>
                    <th style="width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders) :
                    $current_date = null;
                    foreach($orders as $order) :
                        // Date separator row
                        if ( $order->pickup_date !== $current_date ) :
                            $current_date = $order->pickup_date;
                            $formatted_date = date_i18n('l, d F Y', strtotime($current_date));
                            ?>
                            <tr class="lol-date-header">
                                <td colspan="11">📅 <?php echo esc_html($formatted_date); ?></td>
                            </tr>
                        <?php endif;

                        // Items delivered info
                        $items_html = '-';
                        if ( isset($all_items[$order->id]) ) {
                            $item_parts = array();
                            foreach ($all_items[$order->id] as $item) {
                                $del = intval($item->delivered_quantity);
                                $tot = intval($item->quantity);
                                $css_class = ($del >= $tot) ? 'delivered-ok' : 'delivered-partial';
                                $item_parts[] = '<span class="' . $css_class . '">' . $del . '/' . $tot . '</span> ' . esc_html($item->service_type);
                            }
                            $items_html = implode('<br>', $item_parts);
                        }

                        // Payment amount display
                        $amount_html = '-';
                        if ( $order->total_bill_amount > 0 || $order->amount_received > 0 ) {
                            $amount_html = '₹' . number_format($order->amount_received, 0) . ' / ₹' . number_format($order->total_bill_amount, 0);
                            if ( $order->balance_due > 0 ) {
                                $amount_html .= '<br><small style="color: #dc2626;">Due: ₹' . number_format($order->balance_due, 0) . '</small>';
                            }
                        }

                        // WhatsApp actions
                        $wa_actions = '';
                        if ( $order->delivery_date && $order->order_status !== 'Delivered' ) {
                            $msg = "Hello " . $order->customer_name . ", your laundry will be delivered on " . date_i18n('d M Y', strtotime($order->delivery_date)) . ". Token: " . $order->token_id . ". Thank you! — Laugh-O-Laundry";
                            $wa_url = lol_whatsapp_link($order->phone_number, $msg);
                            $wa_actions .= '<a href="' . esc_url($wa_url) . '" target="_blank" class="lol-wa-btn lol-wa-btn-small" title="Notify delivery date">📱 Notify</a>';
                        }
                ?>
                <tr>
                    <td><strong><?php echo esc_html($order->token_id); ?></strong></td>
                    <td><?php echo esc_html($order->customer_name); ?></td>
                    <td><?php echo esc_html($order->phone_number); ?></td>
                    <td><?php echo esc_html($order->pickup_date); ?></td>
                    <td><?php echo $order->delivery_date ? esc_html($order->delivery_date) : '-'; ?></td>
                    <td><?php echo lol_status_badge($order->order_status); ?></td>
                    <td><?php echo lol_payment_badge($order->payment_status); ?></td>
                    <td class="lol-amount-col"><?php echo $amount_html; ?></td>
                    <td class="lol-items-detail"><?php echo $items_html; ?></td>
                    <td><?php echo esc_html($order->delivery_boy ? $order->delivery_boy : '-'); ?></td>
                    <td><?php echo $wa_actions; ?></td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="11">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * Today's Delivery Page
 */
function lol_admin_todays_delivery_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';
    $items_table = $wpdb->prefix . 'laundry_order_items';

    $today = current_time('Y-m-d');
    $today_formatted = date_i18n('l, d F Y', strtotime($today));

    // Fetch all orders scheduled for delivery today
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $orders_table WHERE delivery_date = %s ORDER BY id DESC",
        $today
    ));

    // Pre-fetch items
    $order_ids = array_map(function($o) { return intval($o->id); }, $orders);
    $all_items = array();
    if ( ! empty($order_ids) ) {
        $ids_str = implode(',', $order_ids);
        $items_rows = $wpdb->get_results("SELECT * FROM $items_table WHERE order_id IN ($ids_str)");
        foreach ($items_rows as $item) {
            $all_items[$item->order_id][] = $item;
        }
    }

    $total_count = count($orders);

    lol_admin_page_styles();
    ?>
    <div class="wrap">
        <h1>Today's Delivery</h1>

        <div class="lol-today-banner">
            <span style="font-size: 24px;">🚚</span>
            Deliveries scheduled for <?php echo esc_html($today_formatted); ?>
            <span class="count"><?php echo intval($total_count); ?></span>
        </div>

        <?php if ( $total_count === 0 ) : ?>
            <div style="background: #fff; padding: 40px; text-align: center; border-radius: 10px; border: 1px solid #e5e7eb; margin-top: 10px;">
                <p style="font-size: 18px; color: #9ca3af;">No deliveries scheduled for today.</p>
            </div>
        <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 140px;">Token ID</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Pickup Date</th>
                    <th>Delivery Date</th>
                    <th>Status</th>
                    <th>Payment Status</th>
                    <th>Payment Amount</th>
                    <th>Items Delivered</th>
                    <th>Delivery Boy</th>
                    <th style="width: 160px;">Send Message</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) :
                    // Items delivered info
                    $items_html = '-';
                    if ( isset($all_items[$order->id]) ) {
                        $item_parts = array();
                        foreach ($all_items[$order->id] as $item) {
                            $del = intval($item->delivered_quantity);
                            $tot = intval($item->quantity);
                            $css_class = ($del >= $tot) ? 'delivered-ok' : 'delivered-partial';
                            $item_parts[] = '<span class="' . $css_class . '">' . $del . '/' . $tot . '</span> ' . esc_html($item->service_type);
                        }
                        $items_html = implode('<br>', $item_parts);
                    }

                    // Payment amount
                    $amount_html = '-';
                    if ( $order->total_bill_amount > 0 || $order->amount_received > 0 ) {
                        $amount_html = '₹' . number_format($order->amount_received, 0) . ' / ₹' . number_format($order->total_bill_amount, 0);
                        if ( $order->balance_due > 0 ) {
                            $amount_html .= '<br><small style="color: #dc2626;">Due: ₹' . number_format($order->balance_due, 0) . '</small>';
                        }
                    }

                    // WhatsApp message
                    $payment_line = '';
                    if ( $order->total_bill_amount > 0 ) {
                        $payment_line = "\nTotal Bill: ₹" . number_format($order->total_bill_amount, 0);
                        $payment_line .= "\nAmount Received: ₹" . number_format($order->amount_received, 0);
                        if ( $order->balance_due > 0 ) {
                            $payment_line .= "\nBalance Due: ₹" . number_format($order->balance_due, 0);
                        }
                        $payment_line .= "\nPayment Status: " . $order->payment_status;
                    }

                    $wa_msg = "Hello " . $order->customer_name . ", your laundry delivery is scheduled for today (" . date_i18n('d M Y', strtotime($today)) . ")." . $payment_line . "\nToken: " . $order->token_id . "\nThank you! — Laugh-O-Laundry";
                    $wa_url = lol_whatsapp_link($order->phone_number, $wa_msg);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($order->token_id); ?></strong></td>
                    <td><?php echo esc_html($order->customer_name); ?></td>
                    <td><?php echo esc_html($order->phone_number); ?></td>
                    <td><?php echo esc_html($order->pickup_date); ?></td>
                    <td><?php echo esc_html($order->delivery_date); ?></td>
                    <td><?php echo lol_status_badge($order->order_status); ?></td>
                    <td><?php echo lol_payment_badge($order->payment_status); ?></td>
                    <td class="lol-amount-col"><?php echo $amount_html; ?></td>
                    <td class="lol-items-detail"><?php echo $items_html; ?></td>
                    <td><?php echo esc_html($order->delivery_boy ? $order->delivery_boy : '-'); ?></td>
                    <td>
                        <a href="<?php echo esc_url($wa_url); ?>" target="_blank" class="lol-wa-btn">
                            📱 Send Message
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    <?php
}

function lol_admin_export_page() {
    ?>
    <div class="wrap">
        <h1>Export to Excel</h1>
        <p>Click the button below to export all orders to an Excel (.xlsx) file.</p>
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <input type="hidden" name="action" value="lol_export_excel">
            <?php wp_nonce_field('lol_export_excel_action', 'lol_export_excel_nonce'); ?>
            <button type="submit" class="button button-primary button-hero">Export All Orders</button>
        </form>
    </div>
    <?php
}

function lol_admin_main_excel_page() {
    $excel_url = LOL_THEME_URI . '/Laugh-O-Laundry  Customer Sheet .xlsx';
    ?>
    <div class="wrap">
        <h1>Main Excel Sheet (Editable)</h1>
        <p>Displaying contents of Laugh-O-Laundry Customer Sheet.</p>
        <div style="margin-bottom: 15px;">
            <button id="btn-add-row" class="button button-secondary">Add New Row</button>
            <button id="btn-save-excel" class="button button-primary">Save Changes to Excel</button>
            <span id="save-excel-msg" style="margin-left: 10px; font-weight: bold;"></span>
        </div>
        <div id="lol-excel-container">
            <p>Loading Excel Data...</p>
        </div>
    </div>
    <style>
        #lol-excel-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            background: #fff;
        }
        #lol-excel-table th, #lol-excel-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        #lol-excel-table th {
            background-color: #f2f2f2;
        }
        #lol-excel-table td[contenteditable="true"]:hover {
            background-color: #f9f9f9;
            cursor: text;
        }
        #lol-excel-table td[contenteditable="true"]:focus {
            outline: 2px solid #2271b1;
            background-color: #fff;
        }
    </style>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var url = "<?php echo esc_url($excel_url); ?>";
        var currentSheetName = 'June 2026';
        
        fetch(url + '?t=' + new Date().getTime()) // prevent caching
            .then(function(res) { 
                if (!res.ok) throw new Error("Fetch failed");
                return res.arrayBuffer(); 
            })
            .then(function(ab) {
                var wb = XLSX.read(ab, {type: "array"});
                var targetSheetName = wb.SheetNames.find(function(name) {
                    return name.toLowerCase() === 'june 2026';
                });
                
                if (!targetSheetName) {
                    document.getElementById('lol-excel-container').innerHTML = "<p style='color:red;'>Error: 'June 2026' sheet not found in the Excel file.</p>";
                    return;
                }
                
                currentSheetName = targetSheetName;
                var ws = wb.Sheets[targetSheetName];
                var html = XLSX.utils.sheet_to_html(ws, { id: "lol-excel-table" });
                document.getElementById('lol-excel-container').innerHTML = html;

                // Make cells editable
                makeTableEditable();
            })
            .catch(function(err) {
                document.getElementById('lol-excel-container').innerHTML = "<p style='color:red;'>Error loading Excel file: " + err.message + "</p>";
            });

        function makeTableEditable() {
            var table = document.getElementById('lol-excel-table');
            if (!table) return;
            var tds = table.getElementsByTagName('td');
            for (var i = 0; i < tds.length; i++) {
                tds[i].setAttribute('contenteditable', 'true');
            }
        }

        document.getElementById('btn-add-row').addEventListener('click', function() {
            var table = document.getElementById('lol-excel-table');
            if (!table) return;
            
            var tbody = table.querySelector('tbody') || table;
            var rows = table.getElementsByTagName('tr');
            if (rows.length === 0) return;
            
            var colCount = rows[0].children.length;
            var newRow = document.createElement('tr');
            for (var i = 0; i < colCount; i++) {
                var newTd = document.createElement('td');
                newTd.setAttribute('contenteditable', 'true');
                newRow.appendChild(newTd);
            }
            tbody.appendChild(newRow);
        });

        document.getElementById('btn-save-excel').addEventListener('click', function() {
            var table = document.getElementById('lol-excel-table');
            if (!table) return;
            
            var msgEl = document.getElementById('save-excel-msg');
            msgEl.textContent = "Saving...";
            msgEl.style.color = "#2271b1";
            
            try {
                var newWs = XLSX.utils.table_to_sheet(table);
                var newWb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(newWb, newWs, currentSheetName);
                
                var b64 = XLSX.write(newWb, {bookType:'xlsx', type:'base64'});
                
                var formData = new FormData();
                formData.append('action', 'lol_save_excel_file');
                formData.append('excel_base64', b64);
                
                fetch(ajaxurl, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        msgEl.textContent = "Saved successfully!";
                        msgEl.style.color = "green";

                        // After save, sync delivery dates to DB
                        syncDeliveryDatesToDb(table);

                        setTimeout(() => msgEl.textContent = "", 3000);
                    } else {
                        msgEl.textContent = "Error: " + (res.data ? res.data.message : 'Failed to save.');
                        msgEl.style.color = "red";
                    }
                })
                .catch(err => {
                    msgEl.textContent = "Request failed.";
                    msgEl.style.color = "red";
                });
            } catch (e) {
                msgEl.textContent = "Error generating Excel.";
                msgEl.style.color = "red";
                console.error(e);
            }
        });

        /**
         * After Excel save, scan for delivery dates and sync them to the DB.
         */
        function syncDeliveryDatesToDb(table) {
            var rows = table.querySelectorAll('tr');
            if (rows.length < 2) return;

            // Find header indices
            var headers = [];
            var headerCells = rows[0].querySelectorAll('th, td');
            headerCells.forEach(function(cell) {
                headers.push(cell.textContent.trim().replace(/[^a-z0-9]/gi, '').toLowerCase());
            });

            var idxToken = headers.indexOf('tokenid');
            var idxDelDate = headers.findIndex(function(h) { return h === 'deliverydate'; });
            var idxName = headers.indexOf('name');
            
            if (idxToken === -1 || idxDelDate === -1) return;

            // Collect rows with delivery dates
            var updates = [];
            for (var i = 1; i < rows.length; i++) {
                var cells = rows[i].querySelectorAll('td');
                if (cells.length <= Math.max(idxToken, idxDelDate)) continue;
                
                var tokenVal = cells[idxToken] ? cells[idxToken].textContent.trim() : '';
                var delDateVal = cells[idxDelDate] ? cells[idxDelDate].textContent.trim() : '';
                var nameVal = (idxName !== -1 && cells[idxName]) ? cells[idxName].textContent.trim() : '';

                if (tokenVal && delDateVal) {
                    updates.push({
                        token_id: tokenVal,
                        delivery_date: delDateVal,
                        customer_name: nameVal
                    });
                }
            }

            if (updates.length === 0) return;

            // Send to backend to sync delivery dates
            var formData = new FormData();
            formData.append('action', 'lol_sync_delivery_dates');
            formData.append('updates', JSON.stringify(updates));

            fetch(ajaxurl, { method: 'POST', body: formData })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success && res.data.synced > 0) {
                        console.log('Synced ' + res.data.synced + ' delivery dates to DB.');
                    }
                })
                .catch(function(e) { console.error('Sync error:', e); });
        }
    });
    
    </script>
    <?php
}
