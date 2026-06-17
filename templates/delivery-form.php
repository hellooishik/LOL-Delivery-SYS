<div class="lol-form-wrapper">
    
    <!-- Token Search -->
    <div id="lol-delivery-search" class="lol-search-section">
        <div class="lol-form-group">
            <label for="search_token">Enter Token ID *</label>
            <div class="lol-search-box">
                <input type="text" id="search_token" placeholder="LOL-YYYYMMDD-XXXX">
                <button type="button" id="btn-search-token" class="lol-btn-primary">Search</button>
            </div>
        </div>
        <div id="search-message" class="lol-message"></div>
    </div>

    <!-- Delivery Form (Hidden initially) -->
    <form id="lol-delivery-form" style="display: none;">
        
        <input type="hidden" id="delivery_token_id" name="token_id">
        
        <div class="lol-order-details">
            <h3>Order Details</h3>
            <p><strong>Customer:</strong> <span id="detail_name"></span></p>
            <p><strong>Phone:</strong> <span id="detail_phone"></span></p>
            <p><strong>Pickup Date:</strong> <span id="detail_date"></span></p>
            <p><strong>Status:</strong> <span id="detail_status"></span></p>
            
            <h4>Items:</h4>
            <ul id="detail_items_list"></ul>
        </div>

        <div class="lol-form-group">
            <label for="delivery_boy">Delivery Boy Name *</label>
            <input type="text" id="delivery_boy" name="delivery_boy" required placeholder="Enter delivery boy name">
        </div>

        <div class="lol-form-group">
            <label>Payment Mode</label>
            <div class="lol-radio-group">
                <label>
                    <input type="radio" name="payment_mode" value="Cash" checked>
                    Cash
                </label>
                <label>
                    <input type="radio" name="payment_mode" value="Online">
                    Online
                </label>
            </div>
        </div>

        <div class="lol-form-group">
            <label>Payment Status *</label>
            <div class="lol-radio-group">
                <label>
                    <input type="radio" name="payment_status" value="Unpaid" checked>
                    Unpaid
                </label>
                <label>
                    <input type="radio" name="payment_status" value="Paid">
                    Paid
                </label>
            </div>
        </div>

        <div class="lol-form-group" id="amount_group" style="display: none;">
            <div class="lol-form-group">
                <label for="total_bill_amount">Total Bill Amount (₹)</label>
                <input type="number" id="total_bill_amount" name="total_bill_amount" min="0" step="0.01">
            </div>
            <div class="lol-form-group">
                <label for="amount_received">Amount Received (₹)</label>
                <input type="number" id="amount_received" name="amount_received" min="0" step="0.01">
            </div>
            <div class="lol-form-group">
                <label for="balance_due">Balance Due (₹)</label>
                <input type="number" id="balance_due" name="balance_due" min="0" step="0.01" readonly style="background: #f0f0f0;">
            </div>
        </div>

        <button type="submit" id="btn-submit-delivery" class="lol-btn-primary">MARK AS DELIVERED</button>
        <div id="delivery-message" class="lol-message"></div>
    </form>

</div>
