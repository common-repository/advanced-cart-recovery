<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_AJAX {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Dismiss wp cron notice.
     *
     * @return array
     * @since 1.2.0
     */
    public function acrDismissWPCronNotice(){

        $dismissNotice = get_option( 'acr_dismiss_wp_cron_notice' );

        if( empty( $dismissNotice ) ){
            update_option( 'acr_dismiss_wp_cron_notice', true );

            $response = array(
                            'status'    => 'success',
                            'msg'       => __( 'Notice dismissed.', 'advanced-cart-recovery' )
                        );
        }else{
            $response = array(
                            'status'    => 'error',
                            'msg'       => __( 'Unable to dismiss this notice.', 'advanced-cart-recovery' )
                        );
        }

        do_action( 'acr_dismiss_wp_cron_notice', $response );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){

            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();

        }else return $response;
    }

    /**
     * Blacklist email address.
     *
     * @param string $email
     * @param string $reason
     *
     * @return array
     * @since 1.0.0
     */
    public function acrAddEmailToBlacklist( $email = null, $reason = null ){

        $email      = defined( 'DOING_AJAX' ) && DOING_AJAX ? $_POST[ 'email' ] : $email;
        $reason     = defined( 'DOING_AJAX' ) && DOING_AJAX ? $_POST[ 'reason' ] : $reason;

        $email = sanitize_text_field( $email );
        $reason = sanitize_text_field( $reason );

        $acrBlacklistedEmails = get_option( ACR_BLACKLIST_EMAILS_OPTION );

        do_action( 'acr_before_add_email_to_blacklist', $email, $reason, $acrBlacklistedEmails );

        if ( ! is_array( $acrBlacklistedEmails ) )
            $acrBlacklistedEmails = array();

        if ( ! array_key_exists( $email, $acrBlacklistedEmails ) ){

            $today = current_time( 'timestamp' );
            $acrBlacklistedEmails[ $email ][ 'reason' ] = $reason;
            $acrBlacklistedEmails[ $email ][ 'date' ] = $today;
            update_option( ACR_BLACKLIST_EMAILS_OPTION , $acrBlacklistedEmails );

            $response = array(
                            'status'    => 'success',
                            'email'     => $email,
                            'date'      => date( 'Y-m-d h:i:s A', $today ),
                            'reason'    => ucfirst( $reason ),
                            'msg'       => __( 'Email added successfully', 'advanced-cart-recovery' )
                        );

            do_action( 'acr_add_email_to_blacklist_success', $email, $reason, $acrBlacklistedEmails );

        } else {

            $response = array(
                            'status'    =>  'error',
                            'msg'       =>  sprintf( __( 'The email %1$s has already been blacklisted.' , 'advanced-cart-recovery' ) , $email )
                        );

            do_action( 'acr_add_email_to_blacklist_error', $email, $reason, $acrBlacklistedEmails );

        }

        do_action( 'acr_after_add_email_to_blacklist', $email, $reason, $acrBlacklistedEmails );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){

            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();

        }else return $response;
    }

    /**
     * Remove email address from blacklist.
     *
     * @param string $email
     *
     * @return array
     * @since 1.0.0
     */
    public function acrDeleteEmailFromBlacklist( $email = null ){

        $email = defined( 'DOING_AJAX' ) && DOING_AJAX ? esc_sql( $_POST[ 'email' ] ) : esc_sql( $email );

        $acrBlacklistedEmails = get_option( ACR_BLACKLIST_EMAILS_OPTION );

        if ( ! is_array( $acrBlacklistedEmails ) )
            $acrBlacklistedEmails = array();

        if( array_key_exists( $email, $acrBlacklistedEmails ) ){

            unset( $acrBlacklistedEmails[ $email ] );
            update_option( ACR_BLACKLIST_EMAILS_OPTION , $acrBlacklistedEmails );

            $response = array(
            					'status' 	=> 'success',
            					'email'		=> $email,
            					'msg' 		=> __( 'Successfully Deleted.', 'advanced-cart-recovery' )
            				);

            do_action( 'acr_delete_email_from_blacklist_success', $email, $acrBlacklistedEmails );

        }else{

            $response = array(
                            'status'    => 'error',
                            'email'     => $email,
                            'msg'       => __( 'Email not found.', 'advanced-cart-recovery' )
                        );

            do_action( 'acr_delete_email_from_blacklist_error', $email, $acrBlacklistedEmails );

        }



        do_action( 'acr_after_delete_email_from_blacklist', $email, $acrBlacklistedEmails );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){

            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();

        }else return $response;
    }

    /**
     * Option to view the email schedule details
     *
     * @param string $key
     *
     * @return array
     * @since 1.0.0
     */
    public function acrViewEmailSchedule( $key = null ){

        $key = defined( 'DOING_AJAX' ) && DOING_AJAX ? esc_sql( $_POST[ 'key' ] ) : esc_sql( $key );
        $scheduledData = '';

        $acrEmailSchedules = get_option( ACR_EMAIL_SCHEDULES_OPTION );

        if ( ! is_array( $acrEmailSchedules ) )
            $acrEmailSchedules = array();

        do_action( 'acr_before_view_email_schedule', $key, $acrEmailSchedules );

        if ( array_key_exists( $key, $acrEmailSchedules ) ){

            $scheduledData = $acrEmailSchedules[ $key ];

            $scheduledData[ 'content' ] = html_entity_decode( $scheduledData[ 'content' ], ENT_QUOTES, 'UTF-8' );
            $scheduledData[ 'wrap' ]    = ucfirst( $scheduledData[ 'wrap' ] );

            $response = array(
                                'status'            => 'success',
                                'scheduled_data'    => $scheduledData,
                                'msg'               => __( 'Successfully Added!', 'advanced-cart-recovery' )
                            );

            do_action( 'acr_view_email_schedule_success', $key, $acrEmailSchedules );

        }else{

            $response = array(
                                'status'    => 'error',
                                'msg'       => __( 'Error viewing schedule. Schedule not found!', 'advanced-cart-recovery' )
                            );

            do_action( 'acr_view_email_schedule_error', $key, $acrEmailSchedules );

        }

        do_action( 'acr_after_view_email_schedule', $key, $acrEmailSchedules );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){

            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();

        }else return $response;
    }

    /**
     * Update the email schedule
     *
     * @param string $key
     * @param array $emailFields
     *
     * @return array
     * @since 1.0.0
     */
    public function acrUpdateEmailSchedule( $key = null, $emailFields = null ){

        $key                = defined( 'DOING_AJAX' ) && DOING_AJAX ? $_POST[ 'key' ] : $key;
        $emailFields        = defined( 'DOING_AJAX' ) && DOING_AJAX ? $_POST[ 'email_fields' ] : $emailFields;

        $acrEmailSchedules = get_option( ACR_EMAIL_SCHEDULES_OPTION );

        if ( ! is_array( $acrEmailSchedules ) )
            $acrEmailSchedules = array();

        do_action( 'acr_before_update_email_schedule', $key, $emailFields, $acrEmailSchedules );

        if ( array_key_exists( $key, $acrEmailSchedules) ){

            $acrEmailSchedules[ $key ][ 'subject' ] = stripslashes( $emailFields[ 'subject' ] );
            $acrEmailSchedules[ $key ][ 'wrap' ] = $emailFields[ 'wrap' ];
            $acrEmailSchedules[ $key ][ 'heading_text' ] = ( $emailFields[ 'wrap' ] == 'yes' ) ? stripslashes( $emailFields[ 'heading_text' ] ) : '';
            $acrEmailSchedules[ $key ][ 'days_after_abandoned' ] = $emailFields[ 'days_after_abandoned' ];
            $acrEmailSchedules[ $key ][ 'content' ] = stripslashes( $emailFields[ 'content' ] );

            // Sort email schedules
            uasort( $acrEmailSchedules, array( new ACR_Functions, 'acrSortByArrayKey' ) );

            // Update schedules
            update_option( ACR_EMAIL_SCHEDULES_OPTION , $acrEmailSchedules );

            // Strip tags and limit characters for js to display excerpt
            $emailFields[ 'subject' ] = ACR_Functions::acrContentExcerpt( wc_clean( $acrEmailSchedules[ $key ][ 'subject' ] ), 10 );
            $emailFields[ 'content' ] = ACR_Functions::acrContentExcerpt( wc_clean( $acrEmailSchedules[ $key ][ 'content' ] ), 10 );
            $emailFields[ 'wrap' ] = ucfirst( $emailFields[ 'wrap' ] );

            $response = array(
                                'status'        => 'success',
                                'email_fields'  => $emailFields,
                                'key'           => $key,
                                'msg'           => __( 'Successfully Updated!', 'advanced-cart-recovery' )
                            );

            do_action( 'acr_update_email_schedule_success', $key, $emailFields, $acrEmailSchedules );

        }else{

            $response = array(
                                'status'    => 'error',
                                'msg'       => __( 'Error updating schedule. Schedule not found!', 'advanced-cart-recovery' )
                            );

            do_action( 'acr_update_email_schedule_error', $key, $emailFields, $acrEmailSchedules );

        }


        do_action( 'acr_after_update_email_schedule', $key, $emailFields, $acrEmailSchedules );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){

            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();

        }else return $response;
    }

    /**
     * Perform email sending functionality.
     * Code Update: On v1.1.0 send email can now be used using AJAX request.
     *
     * @param integer $cartID
     * @param int $scheduleID
     * @param array $acrStatus
     * @param string $email
     *
     * @since 1.0.0
     */
    public static function acrSendEmail( $cartID = null, $scheduleID = null, $acrStatus = null, $email = null ){

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

            $cartID     = $_POST[ 'cartID' ];
            $scheduleID = $_POST[ 'scheduleID' ];
            $acrStatus  = $_POST[ 'acrStatus' ];
            $email      = $_POST[ 'email' ];

        }

        $cartID     = sanitize_text_field( $cartID );
        $scheduleID = sanitize_text_field( $scheduleID );
        $email      = sanitize_text_field( $email );

        $wcEmails           = WC_Emails::instance();
        $acrEmails          = ACR_Emails::getInstance();
        $acrEmailSchedules  = get_option( ACR_EMAIL_SCHEDULES_OPTION );

        // Set Request Flag
        $_REQUEST[ 'acr_email_send' ] = true;
        $_REQUEST[ 'acr_email_schedule_id' ] = $scheduleID;

        // Template Tags
        $tags[ 'product_list' ]     = apply_filters( 'acr_email_product_list', $acrEmails->acrGetCartInfo( $cartID, 'product_list' ) );
        $tags[ 'full_name' ]        = apply_filters( 'acr_email_full_name', $acrEmails->acrGetCartInfo( $cartID, 'full_name' ) );
        $tags[ 'first_name' ]       = apply_filters( 'acr_email_first_name', $acrEmails->acrGetCartInfo( $cartID, 'first_name' ) );
        $tags[ 'last_name' ]        = apply_filters( 'acr_email_last_name', $acrEmails->acrGetCartInfo( $cartID, 'last_name' ) );
        $tags[ 'cart_link' ]        = apply_filters( 'acr_email_cart_link', $acrEmails->acrGetCartInfo( $cartID, 'cart_link') );
        $tags[ 'site_url' ]         = apply_filters( 'acr_email_site_url', $acrEmails->acrGetCartInfo( $cartID, 'site_url' ) );
        $tags[ 'site_name' ]        = apply_filters( 'acr_email_site_name', $acrEmails->acrGetCartInfo( $cartID, 'site_name' ) );
        $tags[ 'unsubscribe' ]      = apply_filters( 'acr_email_unsubscribe', $acrEmails->acrGetCartInfo( $cartID, 'unsubscribe') );
        $tags                       = apply_filters( 'acr_email_template_tags', $tags, $cartID, $scheduleID, $acrStatus, $email );

        // Subject
        $excludeFromTitle = apply_filters( 'acr_tags_to_exclude_from_title', array( 'product_list', 'unsubscribe' ) );
        $subject = ! empty( $acrEmailSchedules[ $scheduleID ][ 'subject' ] ) ? $acrEmailSchedules[ $scheduleID ][ 'subject' ] : $acrEmails->acrDefaultTemplate[ 'subject' ];
        $subject = $acrEmails->acrParseEmailContent( $subject, $tags, $excludeFromTitle );
        $subject = apply_filters( 'acr_email_subject', $subject, $tags, $excludeFromTitle );

        // Body
        $template = $acrEmailSchedules[ $scheduleID ][ 'content' ];
        $body = ! empty( $template ) ? $template : $acrEmails->acrDefaultTemplate[ 'body' ];

        // Parse email content
        if( ! empty( $body ) )
            $body = $acrEmails->acrParseEmailContent( $body, $tags );

        // Option to wrap the email using the default WC email header and footer
        $wcHeaderFooter = $acrEmailSchedules[ $scheduleID ][ 'wrap' ];
        $headingText    = $acrEmailSchedules[ $scheduleID ][ 'heading_text' ];
        $headingText    = ! empty( $headingText ) ? $headingText : $subject;

        if( strtolower( $wcHeaderFooter ) == 'yes' )
            $body = $wcEmails->wrap_message( $headingText, $body );

        // Add "powered by" text when premium plugin is not active
        if ( ! is_plugin_active( 'advanced-cart-recovery-premium/advanced-cart-recovery-premium.bootstrap.php' ) ) {

            $poweredByLink = __( '<em>Powered By <a href="https://marketingsuiteplugin.com/product/advanced-cart-recovery/?utm_source=ACR&utm_medium=Powered%20By&utm_campaign=ACR">Advanced Cart Recovery - Marketing Suite</a></em>', 'advanced-cart-recovery' );
            $wcEmailFooter = wp_kses_post( wptexturize( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) );

            if ( $wcHeaderFooter == 'yes' )
                $body = str_replace( $wcEmailFooter, $poweredByLink, $body );
            else
                $body .= '<br><br><br><br>' . wpautop( $poweredByLink );
        }

        // Headers
        $fromName   = $acrEmails->acrWPMailFromName();
        $fromEmail  = $acrEmails->acrWPMailFrom();
        $headers    = $acrEmails->acrConstructEmailHeader( $fromName, $fromEmail );
        $to         = $email;

        // Body Content
        $body = html_entity_decode( $body );
        $body = apply_filters( 'acr_email_body', $body, $tags );

        // Send and update status if success
        $isSent = $wcEmails->send( $to, $subject, $body, $headers );

        if( $isSent === true ){

            if( ! empty( $acrStatus ) ){

                $acrStatus = get_post_meta( $cartID, '_acr_email_status', true );
                $acrStatus[ $scheduleID ][ 'status' ] = 'sent';
                $acrStatus[ $scheduleID ][ 'time_sent' ] = current_time( 'Y-m-d H:i:s', true );

                update_post_meta( $cartID, '_acr_email_status', $acrStatus );

            }

            $response = array(
                                'status'        => 'success',
                                'msg'           => __( 'Email Sent!', 'advanced-cart-recovery' ),
                                'cartID'        => $cartID,
                                'scheduleID'    => $scheduleID,
                                'acrStatus'     => $acrStatus,
                                'email'         => $email,
                                'tags'          => $tags,
                                'timeSent'      => get_date_from_gmt( current_time( 'Y-m-d H:i:s', true ), 'F j, Y @ g:i A' )
                            );

        }else{

            if( ! empty( $acrStatus ) ){

                $acrStatus = get_post_meta( $cartID, '_acr_email_status', true );
                $acrStatus[ $scheduleID ][ 'status' ] = 'failed';
                $acrStatus[ $scheduleID ][ 'time_failed' ] = current_time( 'Y-m-d H:i:s', true );

                update_post_meta( $cartID, '_acr_email_status', $acrStatus );

            }

            $response = array(
                                'status'        => 'error',
                                'timeFailed'    => get_date_from_gmt( current_time( 'Y-m-d H:i:s', true ), 'F j, Y @ g:i A' ),
                                'msg'           => __( 'Email Failed!', 'advanced-cart-recovery' )
                            );

        }

        do_action( 'acr_send_email', $cartID, $scheduleID, $acrStatus, $email, $isSent, $response );

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){

            header( 'Content-Type: application/json' );
            echo json_encode( $response );
            die();

        }else return $response;

    }
}
