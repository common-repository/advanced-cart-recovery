<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Cron {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
    * Run cron manually
    *
    * @since 1.0.0
    */
    public function acrRunCronManually(){

        // Return directly if url parameter has no debug=true
        if( isset( $_GET[ 'debug'] ) && $_GET[ 'debug'] != true ) return;

        if( isset( $_GET[ 'action'] ) && isset( $_GET[ 'hook-name'] ) && $_GET[ 'action' ] == 'acr-manual-cron' ) {

            if( ! current_user_can( 'manage_options' ) ) die( __( 'You are not allowed to run cron events.', 'advanced-cart-recovery' ) );

            $hookName = $_GET[ 'hook-name' ];
            check_admin_referer( 'acr-manual-' . $hookName );

            if( $_GET[ 'hook-name'] == 'acr_email_sender_cron' )
                $msgID = 1;
            elseif( $_GET[ 'hook-name'] == 'acr_cancelled_cart_cron' )
                $msgID = 2;
            elseif( $_GET[ 'hook-name'] == 'acr_abandoned_cart_cron' )
                $msgID = 5;

            if( $this->acrExecuteCron( $hookName, 'manual-run' ) ) {
                wp_redirect( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_help_section&msg=' . $msgID . '&cron=' . $hookName . '&debug=true' );
            }else{
                wp_redirect( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_help_section&msg=error&cron=' . $hookName . '&debug=true' );
            }

        }elseif( isset( $_GET[ 'action'] ) && $_GET[ 'action'] == 'acr_manual_run_clear_all_emails' ){

            check_admin_referer( 'acr-manual-' . $_GET[ 'action'] );

            if( $this->acrExecuteCron( ACR_EMAIL_SENDER_CRON, 'unschedule-hook' ) ){
                wp_redirect( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_help_section&msg=3&debug=true' );
            }else{
                wp_redirect( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_help_section&msg=error&debug=true' );
            }

        }elseif( isset( $_GET[ 'action'] ) && $_GET[ 'action'] == 'acr_manual_run_clear_all_abandoned_carts' ){

            check_admin_referer( 'acr-manual-' . $_GET[ 'action'] );

            if( $this->acrSettingsClearAllAbandonedCarts() ){
                wp_redirect( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_help_section&msg=4&debug=true' );
            }else{
                wp_redirect( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_help_section&msg=error&debug=true' );
            }

        }

        do_action( 'acr_settings_run_cron_manually' );

    }

    /**
    * Execute cron by action
    *
    * @param string $hookName
    * @param string $action
    *
    * @return boolean
    * @since 1.0.0
    */
    public function acrExecuteCron( $hookname, $action ) {

        $metaKey = '';
        $continue = false;

        if( $hookname == ACR_ABANDONED_CART_CRON ){

            $acrAbandonedStatuses = get_option( 'acr_general_status_considered_abandoned' );
            $postType = 'shop_order';
            $metaKey = ACR_ABANDONED_CART_CRON_ARGS;

        }elseif( $hookname == ACR_EMAIL_SENDER_CRON ){
            $metaKey = ACR_EMAIL_SENDER_CRON_ARGS;
        }elseif( $hookname == ACR_CANCELLED_CART_CRON ){
            $metaKey = ACR_CANCELLED_CART_CRON_ARGS;
        }

        $sqlArgs = array(
                            'post_type'     => ! empty( $postType ) ? $postType : ACR_CPT_NAME,
                            'post_status'   => ! empty( $acrAbandonedStatuses ) ? $acrAbandonedStatuses : 'acr-not-recovered',
                            'meta_query'    => array(
                                            array(
                                                'key'     => $metaKey,
                                                'value'   => '',
                                                'compare' => '!=',
                                            )
                                        )
                        );

        $items = new WP_Query( $sqlArgs );

        if ( $items->have_posts() ) {

            $continue = true;

            while ( $items->have_posts() ) { $items->the_post();

                $cartID = get_the_id();
                $args = get_post_meta( $cartID, $metaKey, true );

                switch ( $action ) {
                    case 'manual-run':

                        if( $hookname == ACR_EMAIL_SENDER_CRON ){

                            // Emails can have multiple schedules so we need to loop
                            foreach ( $args as $key => $arg ) {

                                // Unschedule to avoid duplicate
                                $timestamp = wp_next_scheduled( $hookname, $arg );
                                wp_unschedule_event( $timestamp, $hookname, $arg );

                                // Running it now
                                wp_schedule_single_event( current_time( 'timestamp', true ) - 1, $hookname, $arg );

                            }

                        }else{

                            // Unschedule to avoid duplicate
                            $timestamp = wp_next_scheduled( $hookname, $args );
                            wp_unschedule_event( $timestamp, $hookname, $args );

                            // Running it now
                            wp_schedule_single_event( current_time( 'timestamp', true ) - 1, $hookname, $args );

                        }

                        break;

                    case 'unschedule-hook':

                        if( $hookname == ACR_EMAIL_SENDER_CRON ){

                            foreach ( $args as $key => $arg ) {

                                // Unschedule
                                $timestamp = wp_next_scheduled( $hookname, $arg );
                                wp_unschedule_event( $timestamp, $hookname, $arg );

                                foreach ( $arg[ 1 ] as $emailKey => $email ) {

                                    $acrStatus = get_post_meta( $cartID, '_acr_email_status', true );
                                    $acrStatus[ $emailKey ][ 'status' ] = 'failed';
                                    $acrStatus[ $emailKey ][ 'time_failed' ] = current_time( 'Y-m-d H:i:s', true );

                                    update_post_meta( $cartID, '_acr_email_status', $acrStatus );

                                }
                            }

                        }else{

                            // Unschedule
                            $timestamp = wp_next_scheduled( $hookname, $args );
                            wp_unschedule_event( $timestamp, $hookname, $args );

                        }

                        break;

                    default:
                        break;
                }

                // Delete post meta
                delete_post_meta( $cartID, $metaKey );

            }
        }

        do_action( 'acr_settings_run_cron', $continue, $hookname, $metaKey );

        return $continue;

    }

    /**
     * Option to clear all abandoned carts and remove any attached/running cron on the cart object.
     *
     * @return boolean
     * @since 1.0.0
     */
    public function acrSettingsClearAllAbandonedCarts(){

        $continue = false;

        $args = array(
                    'post_type'     => ACR_CPT_NAME,
                    'post_status'   => array( 'acr-not-recovered' )
                );

        $items = new WP_Query( $args );

        if ( $items->have_posts() ) {

            $continue = true;
            while ( $items->have_posts() ) { $items->the_post();

                $cartID = get_the_id();
                $this->acrUnscheduleCronEventsByCartID( $cartID );

                // We now remove the cart object
                wp_delete_post( $cartID, true );

            }
        }

        do_action( 'acr_settings_clear_all_abandoned_carts', $continue, $items );

        return apply_filters( 'acr_clear_all_abandoned_carts', $continue );

    }

    /**
     * Unschedule cron events attached on the Cart Object ID.
     *
     * @param int $cartID
     *
     * @return boolean
     * @since 1.0.0
     */
    public function acrUnscheduleCronEventsByCartID( $cartID ){

        $emailSenderArgs    = get_post_meta( $cartID, ACR_EMAIL_SENDER_CRON_ARGS, true );
        $cancelledCartArgs  = get_post_meta( $cartID, ACR_CANCELLED_CART_CRON_ARGS, true );

        // For email sender cron
        if( ! empty( $emailSenderArgs ) ) {

            // Emails can have multiple schedules so we need to loop
            foreach ( $emailSenderArgs as $key => $args ) {

                // Unschedule to avoid duplicate
                $timestamp = wp_next_scheduled( ACR_EMAIL_SENDER_CRON, $args );
                wp_unschedule_event( $timestamp, ACR_EMAIL_SENDER_CRON, $args );

            }
        }

        // For cancelled cart cron
        if( ! empty( $cancelledCartArgs ) ) {

            // Unschedule to avoid duplicate
            $timestamp = wp_next_scheduled( ACR_CANCELLED_CART_CRON, $cancelledCartArgs );
            wp_unschedule_event( $timestamp, ACR_CANCELLED_CART_CRON, $cancelledCartArgs );

        }

        do_action( 'acr_unschedule_cron_events_by_cart_id', $cartID, $emailSenderArgs, $cancelledCartArgs );

    }

    /**
    * Add admin notices for manual cron
    *
    * @since 1.0.0
    */
    public function acrAddAdminNotices() {

        if( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'acr_settings' &&
            isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'acr_settings_help_section' &&
            isset( $_GET[ 'msg' ] ) &&
            isset( $_GET[ 'debug' ] ) && $_GET[ 'debug' ] == 'true' ) {

            $messages = array(
                            '1' => array(
                                        'status'    => 'updated',
                                        'msg'       => __( 'Successfully run email sender.', 'advanced-cart-recovery' ) ) ,
                            '2' => array(
                                        'status'    => 'updated',
                                        'msg'       => __( 'Successfully run cancelled carts.', 'advanced-cart-recovery' ) ) ,
                            '3' => array(
                                        'status'    => 'updated',
                                        'msg'       => __( 'All scheduled emails are removed successfully.', 'advanced-cart-recovery' ) ) ,
                            '4' => array(
                                        'status'    => 'updated',
                                        'msg'       => __( 'All not recovered ( abandoned ) carts are removed successfully.', 'advanced-cart-recovery' ) ) ,
                            '5' => array(
                                        'status'    => 'updated',
                                        'msg'       => __( 'Successfully run abandoned carts.', 'advanced-cart-recovery' ) ) ,
                            'error' => array(
                                        'status'    => 'error',
                                        'msg'       => __( 'Error! This action can\'t be completed, nothing to run.', 'advanced-cart-recovery' ) ),
                        );

            $msg = $messages[ $_GET[ 'msg' ] ][ 'msg' ];
            $status = $messages[ $_GET[ 'msg' ] ][ 'status' ];

            echo '<div id="message" class="' . $status . ' fade"><p>' . $msg . '</p></div>';

        }

        do_action( 'acr_add_admin_notices' );

    }

    /**
     * Schedule a unique cron single event to turn the cart abandoned
     *
     * @param int $userID
     * @param string $userEmail
     * @param int $orderID
     *
     * @since 1.0.0
     * @since 1.2.0 WooCommerce Product Addons Integration. Add 4th arg which contains wc cart_contents.
     *              When function acrGenerateNewCPTEntry() is executed we store this data into ACR postmeta which to be used later on when restoring the products to cart with the addons.
     */
    public function acrRegisterCartAbandonedEventSchedule( $userID, $userEmail, $orderID ){

        $proceed = apply_filters( 'acr_proceed_cart_abandoned_scheduling', true, $userID, $userEmail, $orderID );
        $acr = ACR_Cart_Recovery::getInstance();

        if( $proceed === false )
            return;

        if( ACR_Functions::acrEmailAddressIsBlacklisted( $userEmail ) )
            return;

        if ( ACR_Functions::acrCheckUserHasRecentCompletedOrder( $userEmail , $orderID ) )
            return;

        $timeUnit           = 'Hours';
        $today              = current_time( 'Y-m-d H:i:s', true );
        $abandonedTime      = get_option( 'acr_general_cart_abandoned_time' );
        $abandonedTime      = ! empty( $abandonedTime ) ? $abandonedTime . ' ' . $timeUnit : '6 ' . $timeUnit;
        $abandonedTime      = apply_filters( 'acr_abandoned_time', $abandonedTime, $userID, $userEmail, $orderID );
        $timeToExecute      = strtotime( '+' . $abandonedTime, strtotime( $today ) );
        $args               = array( $userID, $userEmail, $orderID );

        // WooCommerce Product Addons Integration
        $acrAddon = ACR_Product_Addons::getInstance();
        if( $acrAddon->acrAddonPluginiActiveCheck() && ! is_null( WC()->cart->cart_contents ) ){

            $cartContents = WC()->cart->cart_contents;
            $filter = array( 'addons', 'product_id', 'variation_id' );
            foreach ( $cartContents as $key => $data ) {
                $cartContents[ $key ] = array_intersect_key( $cartContents[ $key ], array_flip( $filter ) );
            }
            array_push( $args, $cartContents );

        }

        // Schedule Email
        wp_schedule_single_event( $timeToExecute, ACR_ABANDONED_CART_CRON, $args );

        // Store Abandoned args into order postmeta since no ACR CPT entry is created yet
        update_post_meta( $orderID, ACR_ABANDONED_CART_CRON_ARGS, $args );

        do_action( 'acr_register_abandoned_cart_schedule', $proceed, $userID, $userEmail, $orderID );

    }

    /**
     * Schedule a unique cron single event to run the email sender function
     *
     * @param int $cartID
     * @param array $emailKeys
     * @param bool $reschedule
     *
     * @since 1.0.0
     */
    public function acrScheduleEmailEvent( $cartID, $emailKeys = array(), $reschedule = false ){

        $proceed = apply_filters( 'acr_proceed_email_scheduling', true, $cartID );

        if( $proceed === false )
            return;

        $acrEmailSchedules = get_option( ACR_EMAIL_SCHEDULES_OPTION );

        $acrEmails      = ACR_Emails::getInstance();
        $email          = $acrEmails->acrGetCartInfo( $cartID, 'recipient_email' );
        $acrArgs        = array();
        $acrEmailStatus = array();

        foreach ( $acrEmailSchedules as $key => $val ) {

            $acrStatus = array();
            $acrOnlyInitial = apply_filters( 'acr_only_initial_template', $key === 'initial' ? true : false );

            if( $acrOnlyInitial ){

                // Skip scheduling this email key
                if( $reschedule === true && ! array_key_exists( $key, $emailKeys ) ) continue;

                $timeUnit           = 'Days';
                $today              = current_time( 'Y-m-d H:i:s', true );
                $daysAfterAbandoned = $val[ 'days_after_abandoned' ];
                $daysAfterAbandoned = '+ ' . $daysAfterAbandoned . ' ' . $timeUnit;
                $daysAfterAbandoned = apply_filters( 'acr_days_after_abandoned', $daysAfterAbandoned );
                $timeToExecute      = ! empty( $emailKeys[ $key ][ 'execTime' ] ) ? $emailKeys[ $key ][ 'execTime' ] : strtotime( $daysAfterAbandoned, strtotime( $today ) );
                $acrStatus[ $key ]  = array(
                                            'subject'               => $val[ 'subject' ],
                                            'days_after_abandoned'  => $val[ 'days_after_abandoned' ],
                                            'status'                => 'pending',
                                        );

                $args = array( $cartID, $acrStatus, $email );

                array_push( $acrArgs, $args );
                $acrEmailStatus = array_replace( $acrEmailStatus, $acrStatus );

                // Schedule Email
                wp_schedule_single_event( $timeToExecute, ACR_EMAIL_SENDER_CRON, $args );

            }
        }

        // Store Email args into ACR CPT postmeta
        update_post_meta( $cartID, ACR_EMAIL_SENDER_CRON_ARGS, $acrArgs );

        // Store Email Status into ACR CPT postmeta if $reschedule is false
        // True means this is a re-schedule after unscheduling all emails due to trashing the entries.
        // False means after abandoned process schedule emails
        if( $reschedule === false )
            update_post_meta( $cartID, '_acr_email_status', $acrEmailStatus );

        do_action( 'acr_schedule_email_event', $proceed, $cartID, $email, $args );

    }

    /**
     * Schedule abandoned forever cron event after every or last email.
     *
     * @param int $cartID
     * @param mixed $scheduleID
     * @param string $acrStatus
     * @param string $email
     * @param bool $isSent
     * @param array $response
     *
     * @since 1.0.0
     */
    public function acrScheduleAbandonedForeverEvent( $cartID, $scheduleID, $acrStatus, $email, $isSent, $response ){

        $proceed = apply_filters( 'acr_proceed_abandoned_forever_scheduling', true, $cartID );

        if( $proceed === false )
            return;

        if( $isSent === true ){

            $timeUnit                   = 'Days';
            $today                      = current_time( 'Y-m-d H:i:s', true );
            $timeConsideredCancelled    = get_option( 'acr_general_time_considered_cancelled' );
            $timeConsideredCancelled    = ! empty( $timeConsideredCancelled ) ? $timeConsideredCancelled : '7';
            $timeConsideredCancelled    = apply_filters( 'acr_days_after_cancelled', '+' . $timeConsideredCancelled . ' ' . $timeUnit );
            $timeToExecute              = strtotime( $timeConsideredCancelled, strtotime( $today ) );

            $acrEmails  = ACR_Emails::getInstance();
            $cartStatus = ! empty( $cartID ) ? get_post_status( $cartID ) : '';
            $email      = $acrEmails->acrGetCartInfo( $cartID, 'recipient_email' );
            $args       = array( $cartID, $cartStatus, $email );

            // Remove schedule
            wp_clear_scheduled_hook( ACR_CANCELLED_CART_CRON, $args );

            // Update schedule
            wp_schedule_single_event( $timeToExecute, ACR_CANCELLED_CART_CRON, $args );

            // Store Abandoned Forever args into ACR CPT postmeta
            update_post_meta( $cartID, ACR_CANCELLED_CART_CRON_ARGS, $args );

        }


        do_action( 'acr_schedule_abandoned_forver_event', $proceed, $cartID, $scheduleID, $acrStatus, $email, $isSent );

    }

    /**
     * Unschedule all abandoned cart cron events created for the customer
     *
     * @param string $orderEmail
     *
     * @since 1.3.1
     */
    public function acrUnscheduleAbandonedCartCronEvents( $orderEmail ) {

        $args = array(
            'post_type'         =>  'shop_order',
            'posts_per_page'    =>  -1,
            'meta_key'          =>  '_billing_email',
            'meta_value'        =>  $orderEmail,
            'post_status'       =>  wc_get_order_statuses()
        );

        $query = new WP_Query( $args );

        foreach( $query->posts as $order ) {

            $cron_args = array(
                (int) get_post_meta( $order->ID , '_customer_user' , true ),
                get_post_meta( $order->ID , '_billing_email' , true ),
                (int) $order->ID
            );

            // get the timestamp of scheduled event
            $timestamp = wp_next_scheduled( 'acr_abandoned_cart_cron' , $cron_args );

            // unschedule event
            wp_unschedule_event( $timestamp , 'acr_abandoned_cart_cron' , $cron_args );

        }
    }
}
