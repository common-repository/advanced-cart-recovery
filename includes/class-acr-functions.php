<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Functions {

    /**
     * Sort by array key.
     *
     * @param string $key
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     * @since 1.0.0
     */
    public static function acrSortByArrayKey( $arr1, $arr2 ) {

        return $arr1[ 'days_after_abandoned' ] - $arr2[ 'days_after_abandoned' ];

    }

    /**
     * Content excerpt.
     *
     * @param string $text
     * @param int $limit
     *
     * @return string
     * @since 1.0.0
     */
    public static function acrContentExcerpt( $text, $limit ) {

        if ( str_word_count( $text, 0 ) > $limit ) {
            $words = str_word_count( $text, 2 );
            $pos = array_keys( $words );
            $text = substr( $text, 0, $pos[ $limit ] ) . '...';
        }

        return $text;
    }

    /**
     * Check if user email is in the blacklist.
     *
     * @param string $email
     *
     * @return bool
     * @since 1.0.0
     */
    public static function acrEmailAddressIsBlacklisted( $email ){

        // Get blacklisted emails
        $acrBlacklistedEmails = get_option( ACR_BLACKLIST_EMAILS_OPTION );

        if ( ! is_array( $acrBlacklistedEmails ) )
            $acrBlacklistedEmails = array();

        // Don't create new entry if the email is in the blacklist
        if ( array_key_exists( $email, $acrBlacklistedEmails ) )
            return true;

        return false;

    }

    /**
     * This will remove duplicate emails if none of the email schedules were sent yet so we can avoid spamming to users.
     * We only need to check the initial since this is the primary email the user will be receive first.
     *
     * @param string $email
     *
     * @since 1.0.0
     */
    public static function acrRemoveDuplicateEmails( $email ){

        $args = array(
                    'post_type'     => ACR_CPT_NAME,
                    'post_status'   => 'acr-not-recovered',
                    'meta_query'    => array(
                                        array(
                                            'key'     => '_acr_email_address',
                                            'value'   => $email,
                                            'compare' => '=',
                                        )
                                    ),
                );

        $duplicates = new WP_Query( $args );

        if ( $duplicates->have_posts() ) {

            while ( $duplicates->have_posts() ) { $duplicates->the_post();

                $cartID = get_the_id();
                $acrEmailStatus = get_post_meta( $cartID, '_acr_email_status', true );

                foreach ( $acrEmailStatus as $key => $details ) {

                    // Check first if the initial email has not been sent yet
                    if( $key === 'initial' && $details[ 'status' ] === 'pending' ){

                        // Unschedule any events attached to this object before deleting.
                        $acrCron = ACR_Cron::getInstance();
                        $acrCron->acrUnscheduleCronEventsByCartID( $cartID );

                        // Remove duplicate
                        wp_delete_post( $cartID, true );

                    }
                }
            }
        }
    }

    /**
     * Get data about the current woocommerce installation.
     *
     * @since 1.3.2
     * @access public
     * @return array Array of data about the current woocommerce installation.
     */
    public static function get_woocommerce_data() {

        if ( ! function_exists( 'get_plugin_data' ) )
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

        return get_plugin_data( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' );

    }

    /**
     * Get product id. WC 2.7.
     *
     * @since 1.3.2
     * @access public
     *
     * @param WC_Product $product Product object.
     * @return int Product id.
     */
    public static function acrGetProductID( $product ) {

        if ( is_a( $product , 'WC_Product' ) ) {

            $woocommerce_data = self::get_woocommerce_data();

            if ( version_compare( $woocommerce_data[ 'Version' ] , '2.7.0' , '>=' ) || $woocommerce_data[ 'Version' ] === '2.7.0-RC1' )
                return $product->get_id();
            else {

                switch ( $product->product_type ) {

                    case 'simple':
                    case 'variable':
                    case 'external':
                        return $product->id;
                    case 'variation':
                        return $product->variation_id;
                    default:
                        return apply_filters( 'wwp_third_party_product_id' , 0 , $product );

                }

            }

        } else {

            error_log( 'ACR Error : acrGetProductID helper functions expect parameter $product of type WC_Product.' );
            return 0;

        }

    }

    /**
     * Get order id. WC 2.7.
     *
     * @since 1.3.2
     * @access public
     *
     * @param WC_Order $product Product object.
     * @return int Product id.
     */
    public static function acrGetOrderID( $order ) {

        if ( is_a( $order , 'WC_Order' ) ) {

            $woocommerce_data = self::get_woocommerce_data();

            if ( version_compare( $woocommerce_data[ 'Version' ] , '2.7.0' , '>=' ) || $woocommerce_data[ 'Version' ] === '2.7.0-RC1' )
                return $order->get_id();
            else
                return $order->id;

        } else {

            error_log( 'ACR Error : acrGetOrderID helper functions expect parameter $product of type WC_Order.' );
            return 0;

        }

    }

    /**
     * Get line item meta. WC 2.7.
     *
     * @since 1.3.2
     * @access public
     *
     * @param $itemID line_item id.
     * @param WC_Order $order Product object.
     * @return array line_item meta data
     */
    public static function acrGetLineItemMeta( $itemID , $order ) {

        if ( is_a( $order , 'WC_Order' ) ) {

            $woocommerce_data = self::get_woocommerce_data();

            if ( version_compare( $woocommerce_data[ 'Version' ] , '2.7.0' , '>=' ) || $woocommerce_data[ 'Version' ] === '2.7.0-RC1' ) {

                $order_item = new WC_Order_Item_Product( $itemID );
                return $order_item->get_meta_data();

            } else {

                return $order->has_meta( $itemID );
            }
        } else {

            error_log( 'ACR Error : acrGetLineItemMeta helper functions expect parameter $product of type WC_Order.' );
            return 0;

        }
    }

    /**
     * Get order currency. WC 2.7.
     *
     * @since 1.3.2
     * @access public
     *
     * @param WC_Order $order Product object.
     * @return string order currency
     */
    public static function acrGetOrderCurrency( $order ) {

        if ( is_a( $order , 'WC_Order' ) ) {

            $woocommerce_data = self::get_woocommerce_data();

            if ( version_compare( $woocommerce_data[ 'Version' ] , '2.7.0' , '>=' ) || $woocommerce_data[ 'Version' ] === '2.7.0-RC1' )
                return $order->get_currency();
            else
                return $order->get_order_currency();

        } else {

            error_log( 'ACR Error : acrGetOrderCurrency helper functions expect parameter $product of type WC_Order.' );
            return 0;

        }
    }

    /**
     * Check if a customer has a more recent order to the one in comparison
     *
     * @since 1.3.2
     * @access public
     *
     * @param  $userEmail email address of the customer
     * @param  $orderID   ID of the order to be compared
     * @return boolean
     */
    public static function acrCheckUserHasRecentCompletedOrder( $userEmail , $orderID ) {

        $completedStatuses = get_option( 'acr_general_status_considered_completed' , array() );
    	$customerOrders = get_posts( array(
    	    'numberposts' => 1,
    	    'meta_key'    => '_billing_email',
    	    'meta_value'  => $userEmail,
    	    'post_type'   => wc_get_order_types(),
    	    'post_status' => $completedStatuses,
    	) );

    	if ( empty( $customerOrders ) )
    		return false;

    	$orderDate			= get_the_time( 'U' , $orderID );
    	$completedOrderDate = strtotime( $customerOrders[0]->post_date );

    	if ( $orderDate < $completedOrderDate )
    		return true;
    	else
    		return false;
    }
}
