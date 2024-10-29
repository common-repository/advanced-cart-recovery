<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Custom_Meta_Boxes {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Check if admin ACR CPT edit screen is accessed.
     * Instantiate WC session and cart. The purpose of this is so that we can use some functions inside WC_Cart class.
     * Ex. get_product_subtotal()
     *
     * @since 1.0.0
     */
    public function acrCheckScreen(){

        $screen = get_current_screen();

        if( $screen->id === ACR_CPT_NAME ){

            include_once( WC()->plugin_path() . '/includes/abstracts/abstract-wc-session.php' );
            include_once( WC()->plugin_path() . '/includes/wc-cart-functions.php' );

            // Instantiate WC session and cart.
            WC()->session  = new WC_Session_Handler();
            WC()->cart     = new WC_Cart();

        }

        do_action( 'acr_check_screen', $screen );

    }

    /**
     * Removes meta boxes
     *
     * @since 1.0.0
     */
    public function acrRemoveMetaBoxes(){

        remove_meta_box( 'submitdiv', ACR_CPT_NAME, 'side' );

    }

    /**
     * Add meta boxes to our CPT edit screen
     *
     * @since 1.0.0
     */
    public function acrMetaBoxes(){

        global $post;
        $dateCreated = get_the_date( 'F j, Y @ g:i A', $post->ID );

        add_meta_box( 'acr-cart-recovery-cart-restore-link', __( 'Restore Link', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrRestoreLink' ), ACR_CPT_NAME, 'normal', 'core' );
        add_meta_box( 'acr-cart-recovery-user-details-box', __( 'User Details', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrUserDetails' ), ACR_CPT_NAME, 'normal', 'core' );
        add_meta_box( 'acr-cart-recovery-product-details-box', __( 'Ordered Items', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrProductDetails' ), ACR_CPT_NAME, 'normal', 'core' );
        add_meta_box( 'acr-cart-recovery-email-status-box', __( 'Email Status <small>(Abandoned on ' . $dateCreated . ')</small>', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrEmailStatus' ), ACR_CPT_NAME, 'normal', 'core' );
        add_meta_box( 'acr-cart-recovery-order-reference', __( 'Order Reference', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrOrderReference' ), ACR_CPT_NAME, 'side', 'core' );
        add_meta_box( 'acr-cart-recovery-status', __( 'Cart Status', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrCartStatus' ), ACR_CPT_NAME, 'side', 'core' );

        // Show upsell graphics
        if( apply_filters( 'acr_show_upsells', true ) )
            add_meta_box( 'acr-cart-recovery-upsell', __( 'Premium Add-on', 'advanced-cart-recovery' ), array( self::getInstance(), 'acrUpsells' ), ACR_CPT_NAME, 'side', 'core' );

        do_action( 'acr_metaboxes', $post );

    }

    /**
     * Show the cart restore link so users copy and paste it into their url for cart restoration.
     *
     * @param object $post
     *
     * @since 1.0.0
     */
    public function acrRestoreLink( $post ){

        do_action( 'acr_before_cart_restore_link', $post );

        $cartID = '';
        $cartLink = ' ';

        if( $post->post_type == ACR_CPT_NAME && in_array( $post->post_status, array( 'acr-not-recovered', 'acr-recovered', 'acr-cancelled' ) ) ){

            $cartID = $post->ID;
            $cartLink = trailingslashit( wc_get_cart_url() );
            $cartLink = $cartLink . '?acrid=' . md5( $cartID );

        } ?>

        <p>
            <label for="acr_cart_url">
                <?php _e( 'Shopping Cart URL', 'advanced-cart-recovery' ); ?>
                <a class="copyLink" id="copy' . $cartID . '" data-clipboard-target="#acr_cart_url" type="button">
                    <?php _e( 'Copy Link', 'advanced-cart-recovery' ); ?>
                </a>
            </label>
            <input id="acr_cart_url" type="text" name="acr_cart_url" value="<?php echo apply_filters( 'acr_shopping_cart_restore_url', $cartLink, $post ); ?>" readonly="readonly">
        </p>

        <script>

        var clipboard = new Clipboard( '.copyLink'  );

            clipboard.on( 'success' , function( e ) {

                alert( '<?php _e( 'Copied text to clipboard: ' , 'advanced-cart-recovery' ); ?>' + e.text );
            });

            clipboard.on( 'error' , function( e ) {

                console.log( e );
            });

        </script>

        <?php

        do_action( 'acr_restore_link_metabox', $post );

    }

    /**
     * Display User information in the new meta box
     *
     * @param object $post
     *
     * @since 1.0.0
     */
    public function acrUserDetails( $post ){

        $userID     = get_post_meta( $post->ID, '_acr_cart_customer_id', true );
        $orderID    = get_post_meta( $post->ID, '_acr_order_id', true );
        $order      = new WC_Order( $orderID );
        $userMeta   = '';

        // Registered users.
        if( $userID !== '0' ){

            $user = get_userdata( $userID );
            $userMeta = array_filter( get_user_meta( $userID ) );

        // Non-registered users.
        }else{

            $user       = get_post_meta( $orderID );
            $userMeta   = array();
            foreach ( $user as $key => $value ) {
                $userMeta[ ltrim( $key, '_' ) ] = $value;
            }

        } ?>

        <div class='column'>

            <h4><?php _e( 'General Details', 'advanced-cart-recovery' ); ?></h4>

            <?php
                if( !empty( $user->data->user_login ) ): ?>
                    <label><?php _e( 'Customer: ', 'advanced-cart-recovery' ); ?></label>
                    <a href="<?php echo get_edit_user_link( $userID ); ?>">
                        <?php echo $user->data->user_login; ?>
                    </a><?php
                else: ?>
                    <label><?php _e( 'Customer: ', 'advanced-cart-recovery' ); ?></label>
                    <?php echo $userMeta[ 'billing_first_name' ][ 0 ] . " " . $userMeta[ 'billing_last_name' ][ 0 ];
                endif;

                if( !empty( $user->data->user_email ) ): ?>
                    <label><?php _e( 'Email: ', 'advanced-cart-recovery' ); ?></label>
                    <a href="mailto:<?php echo $user->data->user_email; ?>">
                        <?php echo $user->data->user_email; ?>
                    </a><?php
                else: ?>
                    <label><?php _e( 'Email: ', 'advanced-cart-recovery' ); ?></label>
                    <a href="mailto:<?php echo $userMeta[ 'billing_email' ][ 0 ]; ?>">
                    <?php echo $userMeta[ 'billing_email' ][ 0 ]; ?>
                    </a><?php
                endif;

                if( $userID != '0' ): ?>
                    <label><?php _e( 'Role: ', 'advanced-cart-recovery' ); ?></label><?php
                        foreach ( $user->roles as $key => $value ) {
                            echo ucwords( str_replace( '_', ' ', $value ) ).'<br>';
                        }
                    else: ?>
                        <label><?php _e( 'Role: ', 'advanced-cart-recovery' ); ?></label>Guest <?php
                    endif; ?>

        </div>

        <div class='column'>

            <h4><?php _e( 'Billing Details', 'advanced-cart-recovery' ); ?></h4>

            <label><?php _e( 'Address:', 'advanced-cart-recovery' ); ?></label>
            <?php echo isset( $userMeta[ 'billing_first_name' ][ 0 ] ) ? $userMeta[ 'billing_first_name' ][ 0 ]: ''; ?>
            <?php echo isset( $userMeta[ 'billing_last_name' ][ 0 ] ) ? $userMeta[ 'billing_last_name' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_company' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_company' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_address_1' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_address_1' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_address_2' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_address_2' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_city' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_city' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_postcode' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_postcode' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_country' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_country' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'billing_state' ][ 0 ] ) ? '<br>' . $userMeta[ 'billing_state' ][ 0 ] : ''; ?>

            <label><?php _e( 'Email:', 'advanced-cart-recovery' ); ?></label>
            <?php if( !empty( $userMeta[ 'billing_email' ][ 0 ] ) ): ?>
                <a href="mailto:<?php echo $userMeta[ 'billing_email' ][ 0 ]; ?>">
                    <?php echo $userMeta[ 'billing_email' ][ 0 ]; ?>
                </a>
            <?php endif; ?>

            <label><?php _e( 'Phone:', 'advanced-cart-recovery' ); ?></label>
            <?php echo isset( $userMeta[ 'billing_phone' ][ 0 ] ) ? $userMeta[ 'billing_phone' ][ 0 ] : ''; ?>

        </div>

        <div class='column'>
            <h4><?php _e( 'Shipping Details', 'advanced-cart-recovery' ); ?></h4>

            <label><?php _e( 'Address:', 'advanced-cart-recovery' ); ?></label>
            <?php echo isset( $userMeta[ 'shipping_first_name' ][ 0 ] ) ? $userMeta[ 'shipping_first_name' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_last_name' ][ 0 ] ) ? $userMeta[ 'shipping_last_name' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_company' ][ 0 ] ) ? '<br>' . $userMeta[ 'shipping_company' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_address_1' ][ 0 ] ) ? '<br>' . $userMeta[ 'shipping_address_1' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_address_2' ][ 0 ] ) ? '<br>' . $userMeta[ 'shipping_address_2' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_city' ][ 0 ] ) ? '<br>' . $userMeta[ 'shipping_city' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_postcode' ][ 0 ] ) ? '<br>' . $userMeta[ 'shipping_postcode' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shipping_country' ][ 0 ] ) ? '<br>' . $userMeta[ 'shipping_country' ][ 0 ] : ''; ?>
            <?php echo isset( $userMeta[ 'shippingstate' ][ 0 ] ) ? '<br>' . $userMeta[ 'shippingstate' ][ 0 ] : ''; ?>

        </div><?php

        do_action( 'acr_user_details_metabox', $post );

    }

    /**
     * Display ordered items in a table
     *
     * @param object $post
     *
     * @since 1.0.0
     */
    public function acrProductDetails( $post ){

        $userID     = get_post_meta( $post->ID, '_acr_cart_customer_id', true );
        $orderID    = get_post_meta( $post->ID, '_acr_order_id', true );
        $order      = new WC_Order( $orderID );
        $lineItems  = $order->get_items( apply_filters( 'acr_order_item_types', 'line_item' ) );

        if ( WC()->cart instanceof WC_Cart ) {
            $wcCart = WC()->cart;
        } else {
            $wcCart = new WC_Cart();
        } ?>
        <div class="acr_product_details_wrapper">
            <table cellpadding="0" cellspacing="0" class="acr_product_details" style="width:100%;">
                <thead>
                    <tr>
                        <th class="item" colspan="2"><?php _e( 'Item', 'advanced-cart-recovery' ); ?></th>
                        <th class="item_cost"><?php _e( 'Cost', 'advanced-cart-recovery' ); ?></th>
                        <th class="quantity"><?php _e( 'Qty', 'advanced-cart-recovery' ); ?></th>
                        <th class="line_cost"><?php _e( 'Total', 'advanced-cart-recovery' ); ?></th>
                    </tr>
                </thead>
                <tbody id="order_line_items"><?php

                    foreach ( $lineItems as $itemID => $item )
                        include( ACR_VIEWS_DIR . 'html-order-item.php' );

                    do_action( 'acr_items_after_line_items', ACR_Functions::acrGetOrderID( $order ) ); ?>

                </tbody>
            </table>
        </div>
        <div class="acr-product-data-row acr-cart-totals">
            <table class="table totals">
                <tr>
                    <td colspan="2"><h2><?php _e( 'Cart Totals', 'advanced-cart-recovery' ); ?></h2></td>
                </tr>
                <tr class="cart-subtotal">
                    <th><?php _e( 'Cart Subtotal', 'advanced-cart-recovery' ); ?></th>
                    <td><?php echo apply_filters( 'acr_cart_details_subtotal', wc_price( $order->get_subtotal() ), $post ); ?></td>
                </tr>
                <?php foreach ( $order->get_used_coupons() as $coupon ) :

                    $wcCoupon = new WC_Coupon( $coupon ); ?>

                    <tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $coupon ) ); ?>">
                        <th>Coupon:</th>
                        <td><?php echo $coupon . ' (' . wc_price( $wcCoupon->__get( 'amount' ) ) . ')'; ?></td>
                    </tr>

                <?php endforeach; ?>

                <tr class="order-total">
                    <th><?php _e( 'Order Total', 'advanced-cart-recovery' ); ?></th>
                    <th><?php echo apply_filters( 'acr_cart_details_order_total', $order->get_formatted_order_total( wc_tax_enabled() ? 'incl' : '' ), $post ); ?></th>
                </tr>
            </table>

        </div><?php

        do_action( 'acr_product_details_metabox', $post );

    }

    /**
     * Display email status
     *
     * @param object $post
     *
     * @since 1.0.0
     */
    public function acrEmailStatus( $post ){

        $acrEmails = ACR_Emails::getInstance();
        global $post;

        $cartID = (int) $post->ID;

        $acrEmailStatus = get_post_meta( $cartID, '_acr_email_status', true );
        $acrEmailArgs   = get_post_meta( $cartID, ACR_EMAIL_SENDER_CRON_ARGS, true ); ?>

        <div class="acr-email-status">

            <?php do_action( 'acr_before_email_status_table', $post ); ?>

            <table class="table">
                <thead>
                    <tr>
                        <th><?php _e( 'Title', 'advanced-cart-recovery' ); ?></th>
                        <th><?php _e( 'Days After Abandoned', 'advanced-cart-recovery' ); ?></th>
                        <th><?php _e( 'Time Sent or Failed', 'advanced-cart-recovery' ); ?></th>
                        <th><?php _e( 'Status', 'advanced-cart-recovery' ); ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th><?php _e( 'Title', 'advanced-cart-recovery' ); ?></th>
                        <th><?php _e( 'Days After Abandoned', 'advanced-cart-recovery' ); ?></th>
                        <th><?php _e( 'Time Sent or Failed', 'advanced-cart-recovery' ); ?></th>
                        <th><?php _e( 'Status', 'advanced-cart-recovery' ); ?></th>
                    </tr>
                </tfoot>
                <tbody><?php

                    do_action( 'acr_before_email_status_list', $post );

                    if( ! empty( $acrEmailStatus ) ) {
                        foreach ( $acrEmailStatus as $key => $value ) {

                            $acrOnlyInitial = apply_filters( 'acr_only_initial_template', $key === 'initial' ? true : false );

                            if( $acrOnlyInitial ){ ?>

                                <tr>
                                    <td><?php
                                        echo apply_filters( 'acr_template_title', $value[ 'subject' ], $key, $value, $post ); ?>
                                    </td>
                                    <td><?php
                                            $daysAfterAbandoned = $value[ 'days_after_abandoned' ];
                                            $daysAfterAbandoned .= $value[ 'days_after_abandoned' ] > 1 ?  ' Days' : ' Day';

                                            echo apply_filters( 'acr_template_days_after_abandoned', $daysAfterAbandoned, $key, $value, $post ); ?>
                                    </td>
                                    <td><?php
                                        $dateTimeNow    = strtotime( current_time( 'Y-m-d H:i:s' ) );
                                        $templateDate   = '';
                                        if( $value[ 'status' ] == 'sent' ){
                                            $templateDate .= get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $value[ 'time_sent' ] ) ), 'F j, Y @ g:i A' );
                                        }elseif( $value[ 'status' ] == 'failed' ){
                                            $templateDate .= get_date_from_gmt( date( 'Y-m-d H:i:s', strtotime( $value[ 'time_failed' ] ) ), 'F j, Y @ g:i A' );
                                        }else{

                                            $emailArgs = array();
                                            if( ! empty( $acrEmailArgs ) ){
                                                foreach ( $acrEmailArgs as $index => $args ) {
                                                    foreach ( $args[ 1 ] as $emailKey => $email ) {
                                                        if( $key === $emailKey ){
                                                            $emailArgs = $args;
                                                            break 2;
                                                        }
                                                    }
                                                }
                                            }

                                            $acrScheduledDate = wp_next_scheduled( ACR_EMAIL_SENDER_CRON, $emailArgs );

                                            if( $acrScheduledDate > $dateTimeNow ){
                                                $scheduledDate = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $acrScheduledDate ), 'Y-m-d H:i:s' ) );

                                                $templateDate .= date( 'F j, Y @ g:i A ', $scheduledDate );
                                                $templateDate .= sprintf( _x( '( %s remaining )', '%s = time remaining', 'advanced-cart-recovery' ), human_time_diff( $dateTimeNow, $scheduledDate ) );
                                            }else
                                                $templateDate .= '( Queuing... )';
                                        }

                                        echo apply_filters( 'acr_template_date', $templateDate, $key, $value, $post ); ?>
                                    </td>
                                    <td><?php
                                            $acrStatus = '';
                                            if( $value[ 'status' ] == 'pending' ){
                                                $acrStatus .= '<label class="status email-pending">';
                                                $acrStatus .= empty( $value[ 'status' ] ) ? 'Pending' : '';
                                            }elseif( $value[ 'status' ] == 'sent' ){
                                                $acrStatus .= '<label class="status email-sent">';
                                            }elseif( $value[ 'status' ] == 'failed' ){
                                                $acrStatus .= '<label class="status email-failed">';
                                            }
                                            $acrStatus .= ucwords( isset( $value[ 'status' ] ) ? $value[ 'status' ] : '' );
                                            $acrStatus .= '</label>';

                                            echo apply_filters( 'acr_template_status', $acrStatus, $key, $value, $post ); ?>
                                    </td>
                                </tr><?php

                            }
                        }
                    }

                    do_action( 'acr_after_email_status_list', $post ); ?>

                </tbody>
            </table>

            <?php do_action( 'acr_after_email_status_table', $post ); ?>

        </div>
    <?php

    }

    /**
     * Display cart status
     *
     * @param object $post
     *
     * @since 1.0.0
     */
    public function acrOrderReference( $post ){

        $orderID = get_post_meta( $post->ID, '_acr_order_id', true );
        $orderLink = get_admin_url() . 'post.php?post=' . $orderID . '&action=edit';

        if( isset( $orderID ) )
            echo '<a href="' . $orderLink . '">#' . $orderID . '</a>';

        do_action( 'acr_order_reference_metabox', $post );

    }

    /**
     * Display cart status
     *
     * @param object $post
     *
     * @since 1.0.0
     */
    public function acrCartStatus( $post ){

        $status = get_post_status( $post->ID );
        $class = 'status danger';
        $statusDate = '';

        if( $status == 'acr-recovered' ){
            $class  = 'status recovered';
            $date = get_post_meta( $post->ID, '_acr_recovered_date', true );
            $date = '<b>' . date( 'M j, Y @ h:i A', strtotime( $date ) ) . '</b>';
            $statusDate = sprintf( __( 'Date Recovered: %s', 'advanced-cart-recovery' ), $date );
        }elseif( $status == 'acr-not-recovered' ){
            $class  = 'status not-recovered';
            $date = get_post_meta( $post->ID, '_acr_not_recovered_date', true );
            $date = '<b>' . date( 'M j, Y @ h:i A', strtotime( $date ) ) . '</b>';
            $statusDate = sprintf( __( 'Date Not Recovered: %s', 'advanced-cart-recovery' ), $date );
        }elseif( $status == 'acr-cancelled' ){
            $class  = 'status cancelled';
            $date = get_post_meta( $post->ID, '_acr_cancelled_date', true );
            $date = '<b>' . date( 'M j, Y @ h:i A', strtotime( $date ) ) . '</b>';
            $statusDate = sprintf( __( 'Date Cancelled: %s', 'advanced-cart-recovery' ), $date );
        }

        $status = str_replace( 'acr-', '', $status );
        $status = str_replace( '-', ' ', $status );
        $status = ucwords( $status );
        $status = apply_filters( 'acr_cart_status_meta_box', $status );
        echo '<p>Current Status: <span class="' . $class . '">' . $status . '</span></p>';
        echo '<p>' . $statusDate . '</p>';

        do_action( 'acr_cart_status_metabox', $post );

    }

    /**
     * Display upsell graphic
     *
     * @since 1.0.1
     */
    public function acrUpsells( $post ){ ?>

        <style type="text/css">
            div#acr-cart-recovery-upsell div.inside{
                padding: 0px;
                margin: 0px;
                overflow: hidden;
            }
            div#acr-cart-recovery-upsell div.inside a,
            div#acr-cart-recovery-upsell div.inside img{
                float: left;
            }
        </style>
        <a target="_blank" href="https://marketingsuiteplugin.com/product/advanced-cart-recovery/?utm_source=ACR&utm_medium=Settings%20Banner&utm_campaign=ACR">
            <img style="outline: none;" src="<?php echo ACR_IMAGES_URL . 'sidebar-upsells.png'; ?>" alt="<?php _e( 'Advanced Cart Recovery Premium' , 'advanced-cart-recovery' ); ?>"/>
        </a><?php

    }
}
