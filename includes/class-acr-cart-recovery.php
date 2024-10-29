<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Cart_Recovery {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Class constructor
     *
     * @since 1.2.0 Register again cron event for any order from completed to any status considered abandoned in ACR settings.
     */
    public function __construct(){

        $statusConsideredAbandoned = get_option( 'acr_general_status_considered_abandoned' );

        if( ! empty( $statusConsideredAbandoned ) ){
            foreach ( $statusConsideredAbandoned as $status ) {
                $status = str_replace( 'wc-', '', $status );
                add_action( 'woocommerce_order_status_completed_to_'. $status, array( $this, 'acrRegisterCronAgain' ) );
            }
        }

    }

    /**
     * Register Cron event again
     *
     * @param int $orderID
     *
     * @since 1.2.0
     */
    public function acrRegisterCronAgain( $orderID ){

        $acrCron = ACR_Cron::getInstance();

        // Get User ID and Email
        $userID = get_post_meta( $orderID, '_customer_user', true );
        $orderEmail = get_post_meta( $orderID, '_billing_email', true );

        // We won't check userID here possible values are 0 or positive values
        if( ! empty( $orderEmail ) && ! empty( $orderID ) )
            $acrCron->acrRegisterCartAbandonedEventSchedule( (int)$userID, $orderEmail, (int)$orderID );

    }

    /**
     * Used to register new Recovered Cart CPT
     *
     * @since 1.0.0
     */
    public function acrRegisterRecoveredCartsCPT(){

        $labels = array(
            'name'                  => __( 'Abandoned Carts', 'advanced-cart-recovery' ),
            'singular_name'         => __( 'Abandoned Cart', 'advanced-cart-recovery' ),
            'menu_name'             => __( 'Abandoned Carts', 'advanced-cart-recovery' ),
            'name_admin_bar'        => __( 'Abandoned Carts', 'advanced-cart-recovery' ),
            'parent_item_colon'     => __( 'Parent Cart:', 'advanced-cart-recovery' ),
            'all_items'             => __( 'Abandoned Carts', 'advanced-cart-recovery' ),
            'add_new_item'          => __( 'Add New Cart', 'advanced-cart-recovery' ),
            'add_new'               => __( 'Add New', 'advanced-cart-recovery' ),
            'new_item'              => __( 'New Cart', 'advanced-cart-recovery' ),
            'edit_item'             => __( 'Edit Cart', 'advanced-cart-recovery' ),
            'update_item'           => __( 'Update Cart', 'advanced-cart-recovery' ),
            'view_item'             => __( 'View Cart', 'advanced-cart-recovery' ),
            'search_items'          => __( 'Search Cart', 'advanced-cart-recovery' ),
            'not_found'             => __( 'No shopping carts have been abandoned yet.', 'advanced-cart-recovery' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'advanced-cart-recovery' ),
        );

        $labels = apply_filters( 'acr_recovered_carts_cpt_labels', $labels );

        $args = array(
            'label'                 => __( 'abandoned-carts', 'advanced-cart-recovery' ),
            'description'           => __( 'Abandoned Carts', 'advanced-cart-recovery' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => 'woocommerce',
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capabilities'          => array(
                                        'create_posts' => false, // Removes support for the "Add New" function
            ),
            'map_meta_cap'          => true,
            'rewrite'               => array('slug' => 'acr-recovered-carts'),
        );

        $args = apply_filters( 'acr_recovered_carts_cpt_args', $args );

        register_post_type( ACR_CPT_NAME, $args );

    }

    /**
     * Register custom post status for ACR post type
     *
     * @since 1.0.0
     */
    public function acrCreateCustomPostStatus(){

        // When the set status in the settings are met after cart check the cart will update its status to not-recovered
        register_post_status( 'acr-not-recovered', array(
            'label'                     => _x( 'Not Recovered', 'advanced-cart-recovery' ),
            'label_count'               => _n_noop( 'Not Recovered <span class="count">(%s)</span>', 'Not Recovered <span class="count">(%s)</span>', 'advanced-cart-recovery' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ));

        // If the cart is restored and order is successful then update post status to recovered
        register_post_status( 'acr-recovered', array(
            'label'                     => _x( 'Recovered', 'advanced-cart-recovery' ),
            'label_count'               => _n_noop( 'Recovered <span class="count">(%s)</span>', 'Recovered <span class="count">(%s)</span>', 'advanced-cart-recovery' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ));

        // If the user decided to unsubscribe then update post status to Cancelled
        register_post_status( 'acr-cancelled', array(
            'label'                     => _x( 'Cancelled', 'advanced-cart-recovery' ),
            'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'advanced-cart-recovery' ),
            'public'                    => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ));

    }

    /**
     * Set new Column for Recovered Cart CPT
     *
     * @param array $columns
     *
     * @return array
     * @since 1.0.0
     */
    public function acrSetNewAdvancedCartRecoveryColumn( $columns ){

        $columns = array(
                            'cb'                        => '<input type="checkbox" />',
                            'title'                     => __( 'Cart ID', 'advanced-cart-recovery' ),
                            'acr-cart-date'             => __( 'Date Created', 'advanced-cart-recovery' ),
                            'acr-cart-customer'         => __( 'Customer', 'advanced-cart-recovery' ),
                            'acr-cart-quantity'         => __( 'Quantity', 'advanced-cart-recovery' ),
                            'acr-cart-coupons'          => __( 'Coupons', 'advanced-cart-recovery' ),
                            'acr-cart-order-total'      => __( 'Order Total', 'advanced-cart-recovery' ),
                            'acr-cart-order-number'     => __( 'Order #', 'advanced-cart-recovery' ),
                            'acr-cart-status'           => __( 'Status', 'advanced-cart-recovery' ),
                        );

        return apply_filters( 'acr_new_recovered_cart_columns', $columns );

    }

    /**
     * Set actions Column for Recovered Cart CPT
     *
     * @param string $columns
     * @param integer $postID
     *
     * @since 1.0.0
     */
    public function acrAdvancedCartRecoveryNewColumns( $columns, $postID ){

        $userID     = get_post_meta( $postID, '_acr_cart_customer_id', true );
        $orderID    = get_post_meta( $postID, '_acr_order_id', true );
        $order      = new WC_Order( $orderID );

        switch ( $columns ) {

            case 'acr-cart-date':
                $date = get_the_date( 'F j, Y g:i A', $postID );
                echo apply_filters( 'acr_cart_date_column', $date );

                do_action( 'acr_cart_date_column', $columns, $postID );

                break;

            case 'acr-cart-customer':

                // Registered users.
                if( $userID ){

                    $user       = get_userdata( $userID );
                    $userMeta   = array_filter( get_user_meta( $userID ) );

                // Non-registered users.
                }else{

                    $user       = get_post_meta( $orderID );
                    $userMeta   = array();

                    foreach ( $user as $key => $value ) {
                        $userMeta[ ltrim( $key, '_' ) ] = $value;
                    }
                }

                if( !empty( $user->data->user_login ) ): ?>
                    <label><?php _e( 'Customer: ', 'advanced-cart-recovery' ); ?></label>
                    <a href="<?php echo get_edit_user_link($userID); ?>">
                        <?php echo trim( $user->first_name ) . ' ' . trim( $user->last_name ); ?>
                    </a><br/> <?php
                else: ?>
                    <label><?php _e( 'Customer: ', 'advanced-cart-recovery' ); ?></label> <?php
                    echo trim( $userMeta[ 'billing_first_name' ][ 0 ] ) . ' ' . trim( $userMeta[ 'billing_last_name' ][ 0 ] ) . '<br/>';
                endif;

                if( !empty( $user->data->user_email ) ): ?>
                    <label><?php _e( 'Email: ', 'advanced-cart-recovery' ); ?></label>
                    <a href="mailto:<?php echo $user->data->user_email; ?>">
                        <?php echo $user->data->user_email; ?>
                    </a><br/> <?php
                else: ?>
                    <label><?php _e( 'Email: ', 'advanced-cart-recovery' ); ?></label>
                    <a href="mailto:<?php echo $userMeta[ 'billing_email' ][ 0 ]; ?>">
                    <?php echo $userMeta[ 'billing_email' ][ 0 ]; ?>
                    </a><br/> <?php
                endif;

                if( $userID !== '0' ): ?>
                    <label><?php _e( 'Role: ', 'advanced-cart-recovery' ); ?></label><?php
                        foreach ( $user->roles as $key => $value ) {
                            echo ucwords( str_replace( '_', ' ', $value ) ) . '<br>';
                        }
                else: ?>
                        <label><?php _e( 'Role: ', 'advanced-cart-recovery' ); ?></label>Guest <?php
                endif;

                do_action( 'acr_cart_customer', $columns, $postID );

                break;

            case 'acr-cart-quantity':

                $quantity   = 0;
                $items      = $order->get_items();

                if( ! empty( $items ) ){
                    foreach ( $items as $key => $item ) {
                        $quantity += $item[ 'qty' ];
                    }
                }

                echo apply_filters( 'acr_cart_quantity_column', $quantity, $postID );

                do_action( 'acr_cart_quantity_column', $columns, $postID );

                break;

            case 'acr-cart-coupons':

                $coupons    = '';
                $useCoupons = $order->get_used_coupons();

                if( ! empty( $useCoupons ) ){
                    foreach ( $useCoupons as $coupon ){
                        $coupons .= $coupon . ', ';
                    }
                }

                echo apply_filters( 'acr_cart_coupons_column', rtrim( trim( $coupons ), ',' ), $postID );

                do_action( 'acr_cart_coupons_column', $columns, $postID );

                break;

            case 'acr-cart-order-total':

                $items = $order->get_items();
                $cartTotal = 0;

                if( ! empty( $items ) ){
                    foreach ( $items as $key => $item ) {
                        $cartTotal += $item[ 'line_total' ];
                    }
                }

                echo apply_filters( 'acr_cart_order_total_column', wc_price( $cartTotal ), $postID );

                do_action( 'acr_cart_order_total_column', $columns, $postID );

                break;

            case 'acr-cart-order-number':

                $orderID = get_post_meta( $postID, '_acr_order_id', true );
                $orderLink = get_admin_url() . 'post.php?post=' . $orderID . '&action=edit';

                if( isset( $orderID ) )
                    echo sprintf( __( '<a href="%1$s">%2$s</a>', 'advanced-cart-recovery' ), $orderLink, $orderID );

                do_action( 'acr_cart_order_number', $columns, $postID );

                break;

            case 'acr-cart-status':

                $status = get_post_status( $postID );
                $class = 'status danger';

                if( $status == 'acr-recovered' )
                    $class = 'status recovered';
                elseif( $status == 'acr-not-recovered' )
                    $class = 'status not-recovered';
                elseif( $status == 'acr-cancelled' )
                    $class = 'status cancelled';

                $status = str_replace( 'acr-', '', $status );
                $status = str_replace( '-', ' ', $status );
                $status = ucwords( $status );
                $status = apply_filters( 'acr_cart_status_column', $status );
                echo '<span class="' . $class . '">' . $status . '</span>';

                do_action( 'acr_cart_status_column', $columns, $postID );

                break;
        }
    }

    /**
     * Set session flag for our recovery process
     *
     * @param int $cartID
     *
     * @since 1.0.0
     * @since 1.2.0 'ref' was added to recovery session. This is used for ACRP reporting to track the the email schedule used for restoring.
     */
    public function acrSetRecoveryProcessFlag( $cartID ){

        $ref = isset( $_GET[ 'ref' ] ) ? $_GET[ 'ref' ] : '';
        $acrCartSession = array( $cartID, '_acr_is_being_recovered', 'ref' => $ref );
        WC()->session->set( ACR_CPT_NAME, $acrCartSession );

    }

    /**
     * Set cron event to track for abandoned carts after checkout
     *
     * @param integer $orderID
     * @param string $posted
     *
     * @since 1.0.0
     * @since 1.2.0 Added an option to allow recovery with a different email on checkout process other than the email used on cart abandoned.
     */
    public function acrOnPlaceOrder( $orderID, $posted ){

        $acrCartSession = WC()->session->get( ACR_CPT_NAME );

        $acrCron = ACR_Cron::getInstance();

        // Get email
        $orderEmail = get_post_meta( $orderID, '_billing_email', true );

        // Recovery process
        if( isset( $acrCartSession ) && is_numeric( $acrCartSession[ 0 ] ) && isset( $acrCartSession[ 1 ] ) ){

            $acrEmails  = ACR_Emails::getInstance();
            $cartEmail  = $acrEmails->acrGetCartInfo( $acrCartSession[ 0 ], 'recipient_email' );
            $allowDifferentEmail = get_option( 'acr_general_allow_recovery_with_different_email' );
            $emailRef = sanitize_text_field( $acrCartSession[ 'ref' ] );

            // Allow different email for recovery process
            if( $allowDifferentEmail === 'yes' ){

                // Update it with the new order and add recovery process flag
                update_post_meta( $acrCartSession[ 0 ], '_acr_order_id', $orderID );
                update_post_meta( $acrCartSession[ 0 ], '_acr_is_being_recovered', true );
                update_post_meta( $acrCartSession[ 0 ], '_acr_email_ref', $emailRef );

            }else{

                // Abandoned order email must match with the recovery process email
                if( $orderEmail === $cartEmail ){

                    // Update it with the new order and add recovery process flag
                    update_post_meta( $acrCartSession[ 0 ], '_acr_order_id', $orderID );
                    update_post_meta( $acrCartSession[ 0 ], '_acr_is_being_recovered', true );
                    update_post_meta( $acrCartSession[ 0 ], '_acr_email_ref', $emailRef );

                }
            }

            // Clear recovery session
            WC()->session->__unset( ACR_CPT_NAME );

        // Abandoned process
        }else{

            if( is_user_logged_in() )
                $userID = get_current_user_id();
            else // Guest
                $userID = 0;

            // Schedule a unique cron single event to turn this cart abandoned on a specified time
            $acrCron = ACR_Cron::getInstance();
            $acrCron->acrRegisterCartAbandonedEventSchedule( $userID, $orderEmail, $orderID );

        }

        do_action( 'acr_on_place_order', $orderID, $posted );

    }

    /**
     * Make the cart abandoned.
     *
     * @param int $userID
     * @param string $userEmail
     * @param int $orderID
     * @param string $addons
     *
     * @since 1.0.0
     * @since 1.2.0 WooCommerce Product Addons Integration. Replaced $orderStatus variable to $addons, since it is not used.
     */
    public function acrAbandonedCart( $userID, $userEmail, $orderID, $addons ){

        $statusConsideredAbandon = get_option( 'acr_general_status_considered_abandoned' );

        // Check first if the order exist and order status is in $statusConsideredAbandon option
        if( get_post_status( $orderID ) !== false && in_array( get_post_status( $orderID ), $statusConsideredAbandon ) ){

            if( ! is_null( $addons ) )
                $_REQUEST[ 'addons' ] = $addons;

            $acrManager = ACR_Cart_Manager::getInstance();
            $acrManager->acrGenerateNewCPTEntry( $userID, $userEmail, $orderID );

        }

        do_action( 'acr_abandoned_cart_hook', $userID, $userEmail, $orderID );

    }

    /**
     * Change post status to recovered
     *
     * @param int $orderID
     *
     * @since 1.0.0
     */
    public function acrRecoveredCart( $orderID ){

        global $wpdb;

        $acrGetCart         = $wpdb->get_results(  "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_acr_order_id' AND meta_value = " . $orderID . " LIMIT 1" );
        $cartID             = isset( $acrGetCart[ 0 ] ) && isset( $acrGetCart[ 0 ]->post_id ) ? $acrGetCart[ 0 ]->post_id : '';
        $isBeingRecovered   = is_numeric( $cartID ) ? get_post_meta( $cartID, '_acr_is_being_recovered', true ) : '';

        // Change to recovered if the cart was restored by the customer
        if( isset( $cartID ) && get_post_status( $cartID ) === 'acr-not-recovered' && $isBeingRecovered ){

            $statusConsideredCompleted = get_option( 'acr_general_status_considered_completed' );
            $orderStatus = get_post_status( $orderID );

            if( in_array( $orderStatus, $statusConsideredCompleted ) ){

                // Update status to acr-recovered
                $cart = array(
                    'ID'            => $cartID,
                    'post_status'   => 'acr-recovered'
                );

                wp_update_post( $cart );

                // Set date when it was turned into acr-recovered status
                update_post_meta( $cartID, '_acr_recovered_date', current_time( 'Y-m-d H:i:s' ) );

                do_action( 'acr_cart_is_recovered', $orderID, $cartID, $isBeingRecovered );

            }

            do_action( 'acr_after_recovered_cart', $orderID, $cartID, $isBeingRecovered );

        }else{ // Just delete not recovered ACR object

            $args = array(
                    'post_type'     => ACR_CPT_NAME,
                    'post_status'   => 'acr-not-recovered',
                    'meta_query' => array(
                            array(
                                'key'     => '_acr_order_id',
                                'value'   => $orderID,
                                'compare' => '=',
                            ),
                    ),
                );

            $cartsToRemove = new WP_Query( $args );

            if ( $cartsToRemove->have_posts() ) {

                while ( $cartsToRemove->have_posts() ) { $cartsToRemove->the_post();

                    $postID = get_the_id();
                    wp_delete_post( $postID, true );

                }
            }
        }

        do_action( 'acr_recovered_cart_hook', $orderID );

    }

    /**
     * Restore cart contents for abandoned orders.
     *
     * @since 1.0.0
     * @since 1.2.0 Updated the error hook name to 'acr_cart_not_found'. Added a hook 'acr_cart_not_restorable'. We now only allow "Not Recovered" status to be restored.
     */
    public function acrRestoreCartContentsForAbandonedOrders(){

        global $wpdb;

        if ( WC()->cart instanceof WC_Cart ) {
            $wcCart = WC()->cart;
        } else {
            $wcCart = new WC_Cart();
        }

        $cartLink       = trailingslashit( $wcCart->get_cart_url() );
        $acrBundles     = ACR_Bundled_Products::getInstance();
        $acrComposite   = ACR_Composite_Products::getInstance();
        $acrAddon       = ACR_Product_Addons::getInstance();

        if( isset( $_GET[ 'acrid' ] ) ){

            $acrid  = esc_sql( $_GET[ 'acrid' ] );
            $ref    = isset( $_GET[ 'ref' ] ) ? esc_sql( $_GET[ 'ref' ] ) : 'no-email-ref';

            $acrCartExist = $wpdb->get_results(
                $wpdb->prepare(
                        apply_filters( 'acr_cart_exist_query',
                            "SELECT post_id FROM $wpdb->postmeta
                             WHERE meta_key = '_acr_cart_hashed_id'
                             AND meta_value = %s
                             LIMIT 1
                            " ),
                        $acrid
                    )
            );

            do_action( 'acr_before_cart_restore', $acrCartExist );

            // Redirect if cart is not found in the db otherwise continue with the restoration
            if( empty( $acrCartExist ) ){

                $msg    = __( 'Oops! Looks like the cart you were recovering no longer exists.', 'advanced-cart-recovery' );
                $type   = 'error';
                $notice = apply_filters( 'acr_cart_not_found', array( 'msg' => $msg, 'type' => $type ) );

                wc_add_notice( $notice[ 'msg' ], $notice[ 'type' ] );

                return;

            }

            $cartID = $acrCartExist[ 0 ]->post_id;

            // Display notice that the cart is not restorable for any status other than "Not Recovered"
            if( ! empty( $cartID ) && get_post_status( $cartID ) !== 'acr-not-recovered' ){

                $msg    = __( 'Oops! This cart is not restorable.', 'advanced-cart-recovery' );
                $type   = 'error';
                $notice = apply_filters( 'acr_cart_not_restorable', array( 'msg' => $msg, 'type' => $type ) );

                wc_add_notice( $notice[ 'msg' ], $notice[ 'type' ] );

                return;

            }

            // Empty cart first
            $wcCart->empty_cart();
            $wcCart->remove_coupons();

            $userID = get_post_meta( $cartID, '_acr_cart_customer_id', true );
            $orderID = get_post_meta( $cartID, '_acr_order_id', true );
            $order = new WC_Order( $orderID );

            $items = $order->get_items();

            if( ! empty( $items ) ){

                // Add items into cart
                foreach ( $items as $ID => $item ) {

                    $productID      = ! empty( $item[ 'product_id' ] ) ? intval( $item[ 'product_id' ] ) : '';
                    $product        = wc_get_product( $productID );
                    $quantity       = ! empty( $item[ 'qty' ] ) ? intval( $item[ 'qty' ] ) : '';
                    $variationID    = ! empty( $item[ 'variation_id' ] ) ? intval( $item[ 'variation_id' ] ) : '';
                    $variation      = array();
                    $cartItemData   = array();

                    if( isset( $item[ 'variation' ] ) ){
                        foreach ( $item[ 'variation' ] as $key => $value )
                            $variation[ $key ] = $value;
                    }

                    // WooCommerce Addon Plugin
                    if( $acrAddon->acrAddonPluginiActiveCheck() ){
                        $_REQUEST[ 'addons' ] = true;
                        $_REQUEST[ 'cart_id' ] = $cartID;
                    }

                    if ( $product->is_type( 'bundle' ) && ACR_Bundled_Products::acrCheckIfBundledParent( $item ) ){

                        // Set http request variables
                        $acrBundles->acrSetBundledRequestVariables( $item, $productID, $quantity, $product );

                        // Add bundled product
                        $key = WC()->cart->add_to_cart( $productID, $quantity );

                    } elseif ( $product->is_type( 'composite' ) && ACR_Composite_Products::acrCheckIfCompositeParent( $item ) ) {

                        // Set http request variables
                        $acrComposite->acrSetCompositeRequestVariables( $item, $productID, $quantity, $product );

                        // Add composite product
                        $key = WC()->cart->add_to_cart( $productID, $quantity );

                    } else if( empty( $item[ 'item_meta' ][ '_bundled_by' ][ 0 ] ) && empty( $item[ 'item_meta' ][ '_composite_parent' ][ 0 ] ) ) // Only add product if the item is not under bundle or composite product
                        $key = WC()->cart->add_to_cart( $productID, $quantity, $variationID, $variation, $cartItemData );

                    do_action( 'acr_cart_restore', $productID, $quantity, $variationID, $variation, $cartItemData );

                }

                // Apply coupons
                if( apply_filters( 'acr_has_coupon_discount', true ) ){
                    foreach ( $order->get_used_coupons() as $coupon ){

                        // Check if coupon is applied or not, if not apply it
                        if ( ! $wcCart->has_discount( $coupon ) )
                            $wcCart->add_discount( $coupon );

                    }
                }

                do_action( 'acr_after_cart_restore', $cartID, $ref );

                // Redirect after all items are added to cart
                wp_redirect( apply_filters( 'acr_redirect_url', $cartLink ), 301 ); exit;

            }
        }
    }

    /**
     * Add a notice after the cart is successfully restored.
     *
     * @since 1.0.0
     */
    public function acrAddNoticeAfterCartRestore(){

        $restoreMessage = apply_filters( 'acr_cart_restore_success_msg', __( 'Thanks! Your cart was successfully restored.', 'advanced-cart-recovery' ) );
        $noticeType     = apply_filters( 'acr_cart_restore_notice_type', 'success' );

        wc_add_notice( $restoreMessage, $noticeType );

        do_action( 'acr_notice_message' );

    }

    /**
     * Cancelled Cart Checker
     * Run on ACR_CANCELLED_CART_CRON
     *
     * @param int $cartID
     * @param string $cartStatus
     * @param string $email
     *
     * @since 1.0.0
     */
    public function acrCancelledCartChecker( $cartID, $cartStatus, $email ){

        if( get_post_status( $cartID ) !== false && $cartStatus === get_post_status( $cartID ) ){

            // Update status to acr-cancelled
            $cart = array(
                            'ID'            => $cartID,
                            'post_status'   => 'acr-cancelled'
                        );

            wp_update_post( $cart );

            // Set date when it was turned into acr-cancelled status
            update_post_meta( $cartID, '_acr_cancelled_date', current_time( 'Y-m-d H:i:s' ) );

        }

        do_action( 'acr_cancelled_cart_hook' );

    }

    /**
     * Display notice if DISABLE_WP_CRON is enabled
     *
     * @since 1.0.0
     * @since 1.2.0 Option to dismiss the notice.
     */
    public function acrDisplayNoticeIfCronIsDisabled(){

        $dismissNotice = get_option( 'acr_dismiss_wp_cron_notice' );

        if ( empty( $dismissNotice ) && ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON == true ) ) {

            $currentURL = $_SERVER[ "HTTP_HOST" ] . $_SERVER[ "REQUEST_URI" ];
            if ( strpos( $currentURL , '?' ) !== false )
                $modCurrentURL = '//' . $currentURL . '&acr_dismiss_wp_cron_notice=1';
            else
                $modCurrentURL = '//' . $currentURL . '?acr_dismiss_wp_cron_notice=1'; ?>

            <div id="acr-cron-disabled-notice" class="notice notice-error">
                <p><?php _e( 'We found out that you have disabled wp_cron. Please ensure that you have enabled wp_cron so that <b>Advanced Cart Recovery</b> plugin will work properly. Kindly remove any defined DISABLE_WP_CRON constant or place this at the end of your wp-config.php file <code>define( "DISABLE_WP_CRON", false );</code></p>', 'advanced-cart-recovery' ); ?>
                <p><a href="<?php echo $modCurrentURL; ?>" id="acr_dismiss_wp_cron_notice"><?php _e( 'Hide Notice', 'advanced-cart-recovery' ); ?></a></p>
            </div><?php

        }
    }

    /**
     * If a user is deleted, remove any entries associated with it
     *
     * @param int $userID
     *
     * @since 1.1.0
     */
    public function acrDeleteUser( $userID ){

        global $wpdb;

        $acrEntries = $wpdb->get_results(
            $wpdb->prepare(
                    apply_filters( 'acr_get_cart_entries_to_delete_query',
                        "SELECT post_id FROM $wpdb->postmeta
                         WHERE meta_key = '_acr_cart_customer_id'
                         AND meta_value = %s
                        " ),
                    $userID
                )
        );

        $acrEntriesArr = array();
        $acrCron = ACR_Cron::getInstance();

        foreach ( $acrEntries as $entry ) {
            // Remove attached cron to the entry
            $acrCron->acrUnscheduleCronEventsByCartID( $entry->post_id );
            $acrEntriesArr[] = $entry->post_id;
        }

        // Delete entries
        if( ! empty( $acrEntriesArr ) ){

            $acrEntriesArr = implode( ', ', $acrEntriesArr );
            $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE $wpdb->postmeta.post_id IN (" . $acrEntriesArr . ")" );
            $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN (" . $acrEntriesArr . ")" );

        }
    }

    /**
     * Everytime a customer completes an order, we delete all abandoned cart entries of that customer on ACR entry
     * that have no emails sent yet.
     *
     * @since 1.3.1
     * @access public
     *
     * @param int $orderID Order id.
     */
    public function acrDeleteCartsEventsOnOrderSuccess( $orderID ) {

        // Get email
        $orderEmail = get_post_meta( $orderID, '_billing_email', true );

        // Remove Duplicate Emails
        ACR_Functions::acrRemoveDuplicateEmails( $orderEmail );

        // unschedule previously created abandoned cart cron events
        $acrCron = ACR_Cron::getInstance();
        $acrCron->acrUnscheduleAbandonedCartCronEvents( $orderEmail );

    }

}
