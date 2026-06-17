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

function lol_admin_dashboard_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';

    $today = current_time('Y-m-d');
    
    $today_pickups = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE pickup_date = '$today'");
    $today_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE delivery_date = '$today' AND order_status = 'Delivered'");
    $pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE order_status != 'Delivered'");

    ?>
    <div class="wrap">
        <h1>Laundry Management Dashboard</h1>
        
        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 5px; flex: 1;">
                <h3>Today's Pickups</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo intval($today_pickups); ?></p>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 5px; flex: 1;">
                <h3>Today's Deliveries</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo intval($today_deliveries); ?></p>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 5px; flex: 1;">
                <h3>Pending Orders</h3>
                <p style="font-size: 24px; font-weight: bold;"><?php echo intval($pending_orders); ?></p>
            </div>
        </div>
    </div>
    <?php
}

function lol_admin_orders_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'laundry_orders';

    // Handle delete
    if ( isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) ) {
        $id = intval($_GET['id']);
        if ( current_user_can('manage_options') ) {
            $wpdb->delete($orders_table, array('id' => $id));
            $wpdb->delete($wpdb->prefix . 'laundry_order_items', array('order_id' => $id));
            echo '<div class="updated"><p>Order deleted.</p></div>';
        }
    }

    $orders = $wpdb->get_results("SELECT * FROM $orders_table ORDER BY id DESC LIMIT 100");

    ?>
    <div class="wrap">
        <h1>All Orders</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Token ID</th>
                    <th>Customer Name</th>
                    <th>Phone</th>
                    <th>Pickup Date</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Delivery Boy</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders) : foreach($orders as $order) : ?>
                <tr>
                    <td><strong><?php echo esc_html($order->token_id); ?></strong></td>
                    <td><?php echo esc_html($order->customer_name); ?></td>
                    <td><?php echo esc_html($order->phone_number); ?></td>
                    <td><?php echo esc_html($order->pickup_date); ?></td>
                    <td><?php echo esc_html($order->order_status); ?></td>
                    <td><?php echo esc_html($order->payment_status); ?></td>
                    <td><?php echo esc_html($order->delivery_boy ? $order->delivery_boy : '-'); ?></td>
                    <td>
                        <a href="?page=lol-orders&action=delete&id=<?php echo $order->id; ?>" onclick="return confirm('Are you sure?');" style="color:red;">Delete</a>
                    </td>
                </tr>
                <?php endforeach; else : ?>
                <tr><td colspan="7">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
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
        <h1>Main Excel Sheet</h1>
        <p>Displaying contents of Laugh-O-Laundry Customer Sheet.</p>
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
    </style>
    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var url = "<?php echo esc_url($excel_url); ?>";
        fetch(url)
            .then(function(res) { 
                if (!res.ok) throw new Error("Fetch failed");
                return res.arrayBuffer(); 
            })
            .then(function(ab) {
                var wb = XLSX.read(ab, {type: "array"});
                var wsname = wb.SheetNames[0];
                var ws = wb.Sheets[wsname];
                var html = XLSX.utils.sheet_to_html(ws, { id: "lol-excel-table" });
                document.getElementById('lol-excel-container').innerHTML = html;
            })
            .catch(function(err) {
                document.getElementById('lol-excel-container').innerHTML = "<p style='color:red;'>Error loading Excel file: " + err.message + "</p>";
            });
    });
    </script>
    <?php
}
