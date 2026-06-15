<div class="lol-dashboard-container">
    
    <div class="lol-header">
        <h1>Laugh-O-Laundry</h1>
        <div class="lol-user-info">
            <?php 
                $current_user = wp_get_current_user();
                echo esc_html( $current_user->display_name ); 
            ?>
            <a href="<?php echo wp_logout_url( home_url() ); ?>" class="lol-logout">Logout</a>
        </div>
    </div>

    <!-- Main Buttons Screen -->
    <div id="lol-main-menu" class="lol-view active-view">
        <button id="btn-show-pickup" class="lol-main-btn lol-btn-pickup">
            <span class="icon">🧺</span>
            PICK UP
        </button>
        
        <button id="btn-show-delivery" class="lol-main-btn lol-btn-delivery">
            <span class="icon">🚚</span>
            DELIVERY
        </button>
    </div>

    <!-- Pickup View -->
    <div id="lol-pickup-view" class="lol-view">
        <button class="lol-back-btn">← Back</button>
        <h2>New Pickup</h2>
        <?php get_template_part( 'templates/pickup', 'form' ); ?>
    </div>

    <!-- Delivery View -->
    <div id="lol-delivery-view" class="lol-view">
        <button class="lol-back-btn">← Back</button>
        <h2>Delivery</h2>
        <?php get_template_part( 'templates/delivery', 'form' ); ?>
    </div>

</div>
