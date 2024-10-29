<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Cart_Manager {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Add filtering of abandoned carts by its status
     *
     * @since 1.2.0
     */
    public function acrFilterListingsByStatus(){

        global $wpdb;

        $screen = get_current_screen();
        $acrPostStatus = array( 'acr-not-recovered' => 'Not Recovered',
                                'acr-recovered'     => 'Recovered',
                                'acr-cancelled'     => 'Cancelled' );

        if ( $screen->post_type == ACR_CPT_NAME ) {

            $acrFilterStatus = '';
            if( isset( $_GET[ 'acr_filter_status' ] ) )
                $acrFilterStatus = sanitize_text_field( $_GET[ 'acr_filter_status' ] );

            echo '<select name="acr_filter_status">';
            echo '<option value="all">' . __( 'All Status', 'advanced-cart-recovery' ) . '</option>';
            foreach ( $acrPostStatus as $pstatus => $status ) {
                $selected = '';
                if( ! empty( $acrFilterStatus ) && $acrFilterStatus  == $pstatus )
                    $selected = 'selected';

                echo '<option value="' . $pstatus . '" ' . $selected . '>' . $status . '</option>';
            }
            echo '</select>';

        }

    }

    /**
     * Add filtering query of abandoned carts by its status
     *
     * @param string $query
     *
     * @return string
     * @since 1.2.0
     */
    public function acrFilterListingsByStatusQuery( $query ){

        if( is_admin() AND isset( $query->query['post_type'] ) AND $query->query['post_type'] == ACR_CPT_NAME ) {
            if( isset( $_GET[ 'acr_filter_status' ] ) && $_GET[ 'acr_filter_status' ] != 'all' ){

                $qv = &$query->query_vars;
                $qv[ 'post_status' ] = sanitize_text_field( $_GET[ 'acr_filter_status' ] );

            }
        }
    }

    /**
     * Create new CPT entry
     *
     * @param int $userID
     * @param email $userEmail
     * @param int $orderID
     * @param array $orderMeta
     *
     * @return int
     * @since 1.0.0
     */
    public function acrGenerateNewCPTEntry( $userID, $userEmail, $orderID = '', $orderMeta = '' ){

        do_action( 'acr_before_generate_new_entry', $userID, $userEmail, $orderID, $orderMeta );

        $proceed = apply_filters( 'acr_proceed_create_entry', true, $userID, $userEmail, $orderID, $orderMeta );

        if( $proceed === false )
            return;

        if( ACR_Functions::acrEmailAddressIsBlacklisted( $userEmail ) )
            return;

        // Remove Duplicate Emails
        ACR_Functions::acrRemoveDuplicateEmails( $userEmail );

        // insert new post
        $insertPost = array(
                    'post_type'         => ACR_CPT_NAME,
                    'comment_status'    => 'closed',
                    'ping_status'       => 'closed',
                    'post_status'       => 'acr-not-recovered',
                    'post_author'       => 0
                );

        // Insert the post into the db
        $cartID = wp_insert_post( $insertPost );

        $updatePost = array(
            'ID'            => $cartID,
            'post_title'    => 'Cart #' . $cartID,
            'post_name'     => 'Cart #' . $cartID,
        );

        // Update post title and post name
        wp_update_post( $updatePost );

        // Update the post meta
        update_post_meta( $cartID, '_acr_order_id', $orderID );
        update_post_meta( $cartID, '_acr_cart_hashed_id', md5( $cartID ) );
        update_post_meta( $cartID, '_acr_cart_customer_id', $userID );
        update_post_meta( $cartID, '_acr_not_recovered_date', current_time( 'Y-m-d H:i:s' ) );
        update_post_meta( $cartID, '_acr_email_address', $userEmail );

        // Add cart id tracker in order meta
        if( ! empty( $orderID ) )
            update_post_meta( $orderID, '_acr_cart_id', $cartID );

        // WooCommerce Product Addons Integration
        if( isset( $_REQUEST[ 'addons' ] ) && ! is_null( $_REQUEST[ 'addons' ] ) )
            update_post_meta( $cartID, '_acr_cart_contents', $_REQUEST[ 'addons' ] );

        // Schedule emails
        $acrCron = ACR_Cron::getInstance();
        $acrCron->acrScheduleEmailEvent( $cartID );

        do_action( 'acr_after_generate_new_entry', $cartID, $userID, $userEmail, $orderID, $orderMeta );

        return $cartID;

    }

    /**
     * Unset all cron events when ACR CPT entry is trashed.
     *
     * @param int $postID
     *
     * @since 1.0.0
     * @since 1.2.0 When the order is trashed delete also the associated ACR entry
     */
    public function acrTrashACRCPTEntry( $postID ){

        switch ( get_post_type( $postID ) ) {

            case ACR_CPT_NAME:

                $acrCron = ACR_Cron::getInstance();
                $acrCron->acrUnscheduleCronEventsByCartID( $postID );

                do_action( 'acr_trash_acr_cpt_entry', $postID );

                break;

            case 'shop_order':

                $getCartID = get_post_meta( $postID, '_acr_cart_id', true );

                if( $getCartID && in_array( get_post_status( $getCartID ), array( 'acr-not-recovered', 'acr-recovered', 'acr-cancelled' ) ) ){

                    wp_delete_post( $getCartID, true );
                    $acrCron = ACR_Cron::getInstance();
                    $acrCron->acrUnscheduleCronEventsByCartID( $getCartID );

                }

                do_action( 'acr_trash_order_entry', $postID );

                break;

            default:
                return;

        }

        do_action( 'acr_trash_cpt_entry', $postID );

    }

    /**
     * Set cron events when ACR CPT entry is restored from trash.
     *
     * @param int $postID
     *
     * @since 1.0.0
     * @since 1.2.0 Create new ACR entry if the order is restored from trash
     */
    public function acrRestoreACRCPTEntry( $postID ){

        switch ( get_post_type( $postID ) ) {

            case ACR_CPT_NAME:

            	$acrStatus = get_post_meta( $postID, '_acr_email_status', true );

                $emailKeys = array();
                foreach ( $acrStatus as $emailKey => $email ) {
                    if( $email[ 'status' ] === 'pending' ){

                        $timeUnit           = 'Days';
                        $daysAfterAbandoned = $email[ 'days_after_abandoned' ];
                        $daysAfterAbandoned = '+ ' . $daysAfterAbandoned . ' ' . $timeUnit;
                        $daysAfterAbandoned = apply_filters( 'acr_days_after_abandoned', $daysAfterAbandoned );
                        $dateCreated        = get_the_date( 'Y-m-d H:i:s', $postID );
                        $dateCreated        = get_gmt_from_date( $dateCreated );
                        $execTime           = strtotime( $daysAfterAbandoned, strtotime( $dateCreated ) );
                        $today              = current_time( 'Y-m-d H:i:s', true );

                        // If the time still in the future we use the cart creation date
                        if( $execTime > strtotime( $today ) ){

                            $emailKeys[ $emailKey ][ 'execTime' ] = $execTime;

                        }else{

                            // If time already past we use date today
                            $emailKeys[ $emailKey ][ 'execTime' ] = strtotime( $daysAfterAbandoned, strtotime( $today ) );

                        }
                    }
                }

                // If there are pending emails to set then schedule again
                if( ! empty( $emailKeys ) ){
                    $acrCron = ACR_Cron::getInstance();
                    $acrCron->acrScheduleEmailEvent( $postID, $emailKeys, true );
                }

                break;

            case 'shop_order':

                // Get User ID and Email
                $orderStatus    = get_post_status( $postID );
                $userID         = get_post_meta( $postID, '_customer_user', true );
                $orderEmail     = get_post_meta( $postID, '_billing_email', true );

                // We won't check userID here possible values are 0 or positive values
                if( ! empty( $orderEmail ) && ! empty( $postID ) ){

                    $statusConsideredAbandoned = get_option( 'acr_general_status_considered_abandoned' );

                    if( ! empty( $statusConsideredAbandoned ) && in_array( $orderStatus, $statusConsideredAbandoned ) ){
                        $this->acrGenerateNewCPTEntry( $userID, $orderEmail, $postID );
                    }
                }

                break;

            default:
                return;

        }

        do_action( 'acr_restore_cpt_entry', $postID );

    }

    /**
     * Before the entry is delete, we must remove any cron events attached.
     *
     * @param int $postID
     *
     * @since 1.0.0
     */
    public function acrDeleteACRCPTEntry( $postID ){

    	if( get_post_type( $postID ) !== ACR_CPT_NAME ) return;

        $acrCron = ACR_Cron::getInstance();
    	$acrCron->acrUnscheduleCronEventsByCartID( $postID );

        do_action( 'acr_delete_cpt_entry', $postID );

    }
}
