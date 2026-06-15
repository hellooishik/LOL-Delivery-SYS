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
            $('#amount_received').prop('required', true);
        } else {
            $('#amount_group').hide();
            $('#amount_received').prop('required', false).val('');
        }
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

});
