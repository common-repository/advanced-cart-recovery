<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Endpoint {

    private static $_instance;

    /** @var string ACR unsubscribe endpoint name. */
    public $_unsubscribe_endpoint;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    public function __construct(){

    	$this->_unsubscribe_endpoint = apply_filters( 'acr_unsubscribe_endpoint', 'acr-unsubscribe' );

    }

    /**
     * Initialize Endpoint
     *
     * @since 1.0.0
     */
    public function acrEndpointInit(){

    	add_rewrite_endpoint( $this->_unsubscribe_endpoint, EP_ALL );
        add_filter( 'template_include', array( $this, 'acrIncludeUnsubscribeTemplate' ), 10, 1 );

    }

    /**
     * Unsusbribe email, put them in the blacklist and change post status to Cancelled
     *
     * @since 1.0.0
     */
    public function acrCatchEndpointVars(){

	    global $wpdb;

	    if( get_query_var( $this->_unsubscribe_endpoint ) ){

	        if( isset( $_GET[ 'email' ] ) && isset( $_GET[ 'token' ] ) && isset( $_GET[ 'ref' ] ) ){

	            $token      = sanitize_text_field( $_GET[ 'token' ] );
	            $acrEmail   = sanitize_text_field( $_GET[ 'email' ] );
                $ref        = isset( $_GET[ 'ref' ] ) ? sanitize_text_field( $_GET[ 'ref' ] ) : 'no-email-ref';
                $reason     = 'unsubscribe';

	            $acrCartExist = $wpdb->get_results(
	                $wpdb->prepare(
	                        apply_filters( 'acr_unsubscribe_cart_exist_query',
	                            "SELECT post_id FROM $wpdb->postmeta
	                             WHERE meta_key = '_acr_cart_hashed_id'
	                             AND meta_value = %s
	                             LIMIT 1
	                            " ),
	                        $token
	                    )
	            );

	            // Performs security check. Redirect if cart is not found in the db otherwise continue with the unsubscribe
	            if( empty( $acrCartExist ) ){
	                wp_redirect( site_url(), 301 ); exit;
	            }
	            
	            $email = get_post_meta( $acrCartExist[ 0 ]->post_id, '_acr_email_address', true );
	            if( $acrEmail != $email ){
	                wp_redirect( site_url(), 301 ); exit;
	            }

	            // Unsuscribe
	            if( $acrEmail == $email && $token == md5( $acrCartExist[ 0 ]->post_id ) ){

	                $acrBlacklistedEmails = get_option( ACR_BLACKLIST_EMAILS_OPTION );

	                if ( ! is_array( $acrBlacklistedEmails ) )
	                    $acrBlacklistedEmails = array();

	                if ( ! array_key_exists( $email, $acrBlacklistedEmails ) ){
                        $acrBlacklistedEmails[ $email ][ 'reason' ] = $reason;
                        $acrBlacklistedEmails[ $email ][ 'date' ] = current_time( 'timestamp' );
                    }

	                update_option( ACR_BLACKLIST_EMAILS_OPTION , $acrBlacklistedEmails );

	                $similarEmails = $wpdb->get_results(
	                                        $wpdb->prepare( "SELECT p.ID FROM $wpdb->posts as p, $wpdb->postmeta as m 
	                                                            WHERE p.post_type = 'ACR_CPT_NAME'
	                                                                AND p.post_status = 'acr-not-recovered'
	                                                                AND p.ID = m.post_id 
	                                                                AND m.meta_key = '_acr_email_address'
	                                                                AND m.meta_value = %s",
	                                                        sanitize_text_field( $email )
	                                            )
	                                    );

	                foreach ( $similarEmails as $key => $cart ) {

	                    // Update status to cancelled
	                    $cart = array(
	                        'ID'            => $cart->ID,
	                        'post_status'   => 'acr-cancelled'
	                    );

	                    wp_update_post( $cart );

	                }

                    do_action( 'acr_unsubscribe', $acrCartExist[ 0 ]->post_id, $token, $acrEmail, $ref, $reason );

	            }
	        }
	    }
    }

    /**
     * Render unsubscribe template
     *
     * @param string $template
     *
     * @return string
     * @since 1.0.0
     */
    public function acrIncludeUnsubscribeTemplate( $template ){

        if( get_query_var( $this->_unsubscribe_endpoint ) ){    

            if( file_exists( ACR_WC_THEME_DIR . 'acr-unsubscribe.php' ) )

                return ACR_WC_THEME_DIR . 'acr-unsubscribe.php';

            elseif( file_exists( ACR_DIR . 'templates/acr-unsubscribe.php' ) )

                return ACR_DIR . 'templates/acr-unsubscribe.php';
            
        }

        return $template;

    }

    /**
     * Endpoint filter request.
     *
     * @param array $vars
     *
     * @return array
     * @since 1.0.0
     */
    public function acrEndpointFilterRequest( $vars ){

	    if( isset( $vars[ $this->_unsubscribe_endpoint ] ) ) 
	    	$vars[ $this->_unsubscribe_endpoint ] = true;

	    return $vars;

    }

    /**
     * Catch endpoint query vars.
     *
     * @param array $vars
     *
     * @return array
     * @since 1.0.0
     */
    public function acrAddQueryVars( $vars ){

        $vars[] = $this->_unsubscribe_endpoint;
        return $vars;
        
    }
}