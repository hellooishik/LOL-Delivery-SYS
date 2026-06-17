jQuery(document).ready(function($) {

    // --- View Navigation --- //
    $('#btn-show-pickup').click(function() {
        $('.lol-view').removeClass('active-view');
        $('#lol-pickup-view').addClass('active-view');
    });

    $('#btn-show-delivery').click(function() {
        $('.lol-view').removeClass('active-view');
        $('#lol-delivery-view').addClass('active-view');
    });

    $('.lol-back-btn').click(function() {
        $('.lol-view').removeClass('active-view');
        $('#lol-main-menu').addClass('active-view');
        // Reset forms when going back
        $('#lol-pickup-form')[0].reset();
        $('#lol-delivery-form').hide();
        $('#lol-delivery-search').show();
        $('.lol-message').removeClass('error success').text('').hide();
    });

    // --- Pickup Form Logic --- //
    let itemIndex = 1;

    $('#btn-add-item').click(function() {
        let newRow = `
            <div class="lol-item-row">
                <div class="lol-item-col">
                    <label>Qty</label>
                    <input type="number" name="items[${itemIndex}][quantity]" min="1" required class="lol-qty-input">
                </div>
                <div class="lol-item-col lol-flex-grow">
                    <label>Service Type</label>
                    <select name="items[${itemIndex}][service_type]" required class="lol-service-select">
                        <option value="">Select Service</option>
                        <option value="Basic Wash">Basic Wash</option>
                        <option value="Dry Cleaning">Dry Cleaning</option>
                        <option value="Ironing">Ironing</option>
                        <option value="Premium Wash">Premium Wash</option>
                        <option value="Steam Press">Steam Press</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="lol-item-col lol-remove-col">
                    <button type="button" class="lol-remove-item">&times;</button>
                </div>
            </div>
        `;
        $('#lol-items-container').append(newRow);
        itemIndex++;
    });

    $(document).on('click', '.lol-remove-item', function() {
        $(this).closest('.lol-item-row').remove();
    });

    $('#lol-pickup-form').submit(function(e) {
        e.preventDefault();
        let $btn = $('#btn-submit-pickup');
        let $msg = $('#pickup-message');
        
        $btn.prop('disabled', true).text('SAVING...');
        $msg.removeClass('error success').text('').hide();

        let formData = $(this).serialize() + '&action=lol_save_pickup&nonce=' + lol_ajax_obj.nonce;

        $.post(lol_ajax_obj.ajax_url, formData, function(response) {
            if (response.success) {
                // Show success screen
                $('#lol-pickup-form').hide();
                $('#lol-pickup-success').show();
                
                let data = response.data;
                $('#success-token').text(data.token_id);
                $('#success-name').text(data.customer_name);
                $('#success-phone').text(data.phone_number);
                $('#success-date').text(data.pickup_date);
                
                // Update Excel in background
                updateExcelWithOrder('pickup', data);
            } else {
                $msg.addClass('error').text(response.data.message).show();
                $btn.prop('disabled', false).text('SAVE PICKUP');
            }
        }).fail(function() {
            $msg.addClass('error').text('An error occurred. Please try again.').show();
            $btn.prop('disabled', false).text('SAVE PICKUP');
        });
    });

    $('#btn-copy-token').click(function() {
        let token = $('#success-token').text();
        navigator.clipboard.writeText(token).then(function() {
            alert('Token copied to clipboard!');
        });
    });

    $('#btn-new-pickup').click(function() {
        $('#lol-pickup-success').hide();
        $('#lol-pickup-form')[0].reset();
        
        // Reset items to just one row
        $('#lol-items-container').children('.lol-item-row:not(:first)').remove();
        
        $('#btn-submit-pickup').prop('disabled', false).text('SAVE PICKUP');
        $('#lol-pickup-form').show();
    });


    // --- Delivery Form Logic --- //
    $('#btn-search-token').click(function() {
        let token = $('#search_token').val().trim();
        let $msg = $('#search-message');
        let $btn = $(this);

        if (!token) {
            $msg.addClass('error').text('Please enter a Token ID.').show();
            return;
        }

        $btn.prop('disabled', true).text('Searching...');
        $msg.removeClass('error success').text('').hide();

        $.post(lol_ajax_obj.ajax_url, {
            action: 'lol_search_token',
            token_id: token,
            nonce: lol_ajax_obj.nonce
        }, function(response) {
            if (response.success) {
                let order = response.data.order;
                let items = response.data.items;

                $('#delivery_token_id').val(order.token_id);
                $('#detail_name').text(order.customer_name);
                $('#detail_phone').text(order.phone_number);
                $('#detail_date').text(order.pickup_date);
                $('#detail_status').text(order.order_status);

                let itemsHtml = '';
                items.forEach(function(item) {
                    itemsHtml += `<li>${item.quantity} x ${item.service_type}</li>`;
                });
                $('#detail_items_list').html(itemsHtml);

                $('#lol-delivery-search').hide();
                $('#lol-delivery-form').show();
            } else {
                $msg.addClass('error').text(response.data.message).show();
            }
        }).fail(function() {
            $msg.addClass('error').text('An error occurred.').show();
        }).always(function() {
            $btn.prop('disabled', false).text('Search');
        });
    });

    // Payment status toggle
    $('input[name="payment_status"]').change(function() {
        if ($(this).val() === 'Paid') {
            $('#amount_group').show();
            $('#total_bill_amount').prop('required', true);
            $('#amount_received').prop('required', true);
        } else {
            $('#amount_group').hide();
            $('#total_bill_amount').prop('required', false).val('');
            $('#amount_received').prop('required', false).val('');
            $('#balance_due').val('');
        }
    });

    // Balance due calculation
    $('#total_bill_amount, #amount_received').on('input', function() {
        let total = parseFloat($('#total_bill_amount').val()) || 0;
        let received = parseFloat($('#amount_received').val()) || 0;
        let balance = total - received;
        $('#balance_due').val(balance.toFixed(2));
    });

    $('#lol-delivery-form').submit(function(e) {
        e.preventDefault();
        let $btn = $('#btn-submit-delivery');
        let $msg = $('#delivery-message');

        $btn.prop('disabled', true).text('UPDATING...');
        $msg.removeClass('error success').text('').hide();

        let formData = $(this).serialize() + '&action=lol_save_delivery&nonce=' + lol_ajax_obj.nonce;

        $.post(lol_ajax_obj.ajax_url, formData, function(response) {
            if (response.success) {
                // Parse form data for background excel sync
                let formDataArr = $('#lol-delivery-form').serializeArray();
                let orderData = {};
                formDataArr.forEach(item => orderData[item.name] = item.value);
                updateExcelWithOrder('delivery', orderData);

                $msg.addClass('success').text(response.data.message).show();
                setTimeout(function() {
                    $('.lol-back-btn').click(); // Go back to main menu
                }, 2000);
            } else {
                $msg.addClass('error').text(response.data.message).show();
                $btn.prop('disabled', false).text('MARK AS DELIVERED');
            }
        }).fail(function() {
            $msg.addClass('error').text('An error occurred.').show();
            $btn.prop('disabled', false).text('MARK AS DELIVERED');
        });
    });

    // --- Background Excel Updater --- //
    function updateExcelWithOrder(actionType, orderData) {
        if (typeof XLSX === 'undefined') {
            console.error("SheetJS not loaded.");
            return;
        }
        
        fetch(lol_ajax_obj.excel_url + '?t=' + new Date().getTime())
            .then(res => res.arrayBuffer())
            .then(ab => {
                var wb = XLSX.read(ab, {type: "array"});
                var targetSheetName = wb.SheetNames.find(name => name.toLowerCase() === 'june 2026');
                if (!targetSheetName) return;
                
                var ws = wb.Sheets[targetSheetName];
                var data = XLSX.utils.sheet_to_json(ws, {header: 1}); 
                
                var headers = data[0] || [];
                var getColIdx = (name) => headers.findIndex(h => h && h.toString().trim().toLowerCase() === name.toLowerCase());
                
                var idxSl = getColIdx('SL.');
                var idxDate = getColIdx('Date');
                var idxName = getColIdx('Name');
                var idxClothes = getColIdx('No. of clothes');
                var idxAmount = getColIdx('Amount');
                var idxDelDate = getColIdx('Delivery Date');
                var idxDelStatus = getColIdx('Delivery Status');
                var idxToken = getColIdx('Token ID');
                var idxDelPartner = getColIdx('Delivery Partner Name');
                var idxItems = getColIdx('Items Details');
                
                if (actionType === 'pickup') {
                    var maxSl = 0;
                    for (var i=1; i<data.length; i++) {
                        var slVal = parseInt(data[i][idxSl]);
                        if (!isNaN(slVal) && slVal > maxSl) maxSl = slVal;
                    }
                    
                    var newRow = new Array(headers.length).fill('');
                    if (idxSl !== -1) newRow[idxSl] = maxSl + 1;
                    if (idxDate !== -1) newRow[idxDate] = orderData.pickup_date;
                    if (idxName !== -1) newRow[idxName] = orderData.customer_name;
                    
                    var totalClothes = 0;
                    var itemsArr = [];
                    if (orderData.items) {
                        var itemsList = Array.isArray(orderData.items) ? orderData.items : Object.values(orderData.items);
                        itemsList.forEach(item => {
                            totalClothes += parseInt(item.quantity) || 0;
                            itemsArr.push(item.quantity + 'x ' + item.service_type);
                        });
                    }
                    
                    if (idxClothes !== -1) newRow[idxClothes] = totalClothes;
                    if (idxItems !== -1) newRow[idxItems] = itemsArr.join(', ');
                    if (idxToken !== -1) newRow[idxToken] = orderData.token_id;
                    
                    data.push(newRow);
                    
                } else if (actionType === 'delivery') {
                    if (idxToken !== -1) {
                        var rowIndex = data.findIndex(row => row[idxToken] === orderData.token_id);
                        if (rowIndex !== -1) {
                            if (idxDelPartner !== -1) data[rowIndex][idxDelPartner] = orderData.delivery_boy;
                            if (idxDelDate !== -1) data[rowIndex][idxDelDate] = new Date().toISOString().split('T')[0];
                            if (idxDelStatus !== -1) data[rowIndex][idxDelStatus] = 'Delivered';
                            if (idxAmount !== -1 && orderData.amount_received) data[rowIndex][idxAmount] = orderData.amount_received;
                        }
                    }
                }
                
                var newWs = XLSX.utils.aoa_to_sheet(data);
                wb.Sheets[targetSheetName] = newWs;
                
                var b64 = XLSX.write(wb, {bookType:'xlsx', type:'base64'});
                var formData = new FormData();
                formData.append('action', 'lol_save_excel_file');
                formData.append('excel_base64', b64);
                
                fetch(lol_ajax_obj.ajax_url, { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => { if (!res.success) console.error('Failed to sync excel:', res); })
                    .catch(e => console.error(e));
            })
            .catch(err => console.error(err));
    }

});
