<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Emails {

    private static $_instance;

    /** @var array Contains the default email tags, title and content. */
    public $acrDefaultTemplate;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(){

        $this->acrDefaultTemplate = apply_filters( 'acr_default_email_template', array(
                                        'tags'      => array(
                                                            '{product_list}'        => __( 'A formatted table of products that were in the order at the time of abandonment', 'advanced-cart-recovery' ),
                                                            '{full_name}'           => __( 'Combination of the first & last name', 'advanced-cart-recovery' ),
                                                            '{first_name}'          => __( 'First Name', 'advanced-cart-recovery' ),
                                                            '{last_name}'           => __( 'Last Name', 'advanced-cart-recovery' ),
                                                            '{cart_link}'           => __( 'Link to the WooCommerce cart page which has their order pre-filled (pinching functionality from Email Cart here)', 'advanced-cart-recovery' ),
                                                            '{site_url}'            => __( 'The website\'s url', 'advanced-cart-recovery' ),
                                                            '{site_name}'           => __( 'The website\'s name', 'advanced-cart-recovery' ),
                                                            '{unsubscribe}'         => __( 'Unsubscribe Link', 'advanced-cart-recovery' ),
                                                    ),
                                        'subject'   =>  __( 'We noticed you left before you could finish your order ...', 'advanced-cart-recovery' ),
                                        'body'      =>  __( '<p>Hi {first_name}</p>' .
                                                        '<p>Looks like you left our site before you could finish your order recently.</p>' .
                                                        '<p>{product_list}</p>' .
                                                        '<p>Would you like to complete it now?</p>' .
                                                        '<p>Click this link to proceed: {cart_link}</p>' .
                                                        '<p>Regards,</br>' .
                                                        '{site_name} - {site_url}</p>' .
                                                        '<p>Stop receiving abandoned cart notices, click {unsubscribe}', 'advanced-cart-recovery' )
                                        )
                                    );

    }

    /**
     * Perform email check, get the template ID for email then pass to the email sender with other required args.
     *
     * @param int $cartID
     * @param array $acrStatus
     * @param string $email
     *
     * @since 1.0.0
     */
    public function acrEmailSender( $cartID, $acrStatus, $email ){

        $acrEmailSchedules = get_option( ACR_EMAIL_SCHEDULES_OPTION );

        foreach ( $acrStatus as $key => $status ) {

            if( array_key_exists( $key, $acrEmailSchedules ) && get_post_status( $cartID ) !== false )
                ACR_AJAX::acrSendEmail( $cartID, $key, $acrStatus, $email );

        }

        do_action( 'acr_email_sender', $cartID, $acrStatus, $email );

    }

    /**
     * Parse email contents, replace email template tags with appropriate values.
     *
     * @param string $content
     * @param array $tags
     * @param array $exclude
     *
     * @return string
     * @since 1.0.0
     */
    public function acrParseEmailContent( $content, $tags, $exclude = array() ){

        foreach ( $tags as $tag => $val ) {
            if( ! in_array( $tag, $exclude ) ){
        	   $content = str_replace( '{' . $tag . '}', $val , $content );
            }
        }

        return apply_filters( 'acr_parse_email_content', $content, $tags );

    }

    /**
     * This will fetch info about the cart and then return the appropriate values.
     *
     * @param integer $cartID
     * @param string $getInfo
     *
     * @return string
     * @since 1.0.0
     */
	public function acrGetCartInfo( $cartID, $getInfo ){

        $orderID = (int) get_post_meta( $cartID, '_acr_order_id', true );

        if ( WC()->cart instanceof WC_Cart ) {
            $wcCart = WC()->cart;
        } else {
            $wcCart = new WC_Cart();
        }

        $fullName 	= '';
        $firstName 	= '';
        $lastName 	= '';
        $email 		= '';
        $userMeta   = '';

        // Get user info from order
        if( $orderID ){

            $user 		= get_post_meta( $orderID );
            $userMeta 	= array();

            foreach ( $user as $key => $value ) {
                $userMeta[ ltrim( $key, '_' ) ] = $value;
            }

			$fullName 	= trim( $userMeta[ 'billing_first_name' ][ 0 ] . ' ' . $userMeta[ 'billing_last_name' ][ 0 ] );
            $firstName 	= $userMeta[ 'billing_first_name' ][ 0 ];
            $lastName 	= $userMeta[ 'billing_last_name' ][ 0 ];
            $email 	    = $userMeta[ 'billing_email' ][ 0 ];

        }

        switch ( $getInfo ) {
            case 'product_list':

                $order = new WC_Order( $orderID );

                ob_start(); ?>

                <table cellpadding="0" cellspacing="0" class="acr_product_details" style="margin-bottom: 50px; clear: none;">
                    <thead>
                        <tr>
                            <th class="item" colspan="2" style="text-align: left;"><?php _e( 'Item', 'email-cart-for-woocommerce' ); ?></th>
                            <th class="item_cost" style="text-align: left;"><?php _e( 'Cost', 'email-cart-for-woocommerce' ); ?></th>
                            <th class="quantity" style="text-align: left;"><?php _e( 'Qty', 'email-cart-for-woocommerce' ); ?></th>
                            <th class="line_cost" style="text-align: left;"><?php _e( 'Total', 'email-cart-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="product_line_items"><?php

                    $lineItems  = $order->get_items( apply_filters( 'acr_order_item_types', 'line_item' ) );

                    if( ! empty( $lineItems ) ) {
                        foreach ( $lineItems as $itemID => $item ){

                            $product  = $order->get_product_from_item( $item );

                            include( ACR_VIEWS_DIR . 'html-order-item.php' );

                        }
                    } ?>

                    </tbody>
                </table><?php

                return ob_get_clean();

                break;

            case 'full_name':

                return $fullName;

                break;

            case 'first_name':

                return $firstName;

                break;

            case 'last_name':

                return $lastName;

                break;

            case 'recipient_email':

                return $email;

                break;

            case 'cart_link':

                $cartLink = trailingslashit( $wcCart->get_cart_url() );
                $cartLink = $cartLink . '?acrid=' . md5( $cartID );

                // Used to track email schedules used
                if( isset( $_REQUEST[ 'acr_email_schedule_id' ] ) )
                    $cartLink = untrailingslashit( $cartLink ) . '&ref=' . $_REQUEST[ 'acr_email_schedule_id' ];

                return '<a href="' . $cartLink . '">' . $cartLink . '</a>';

                break;

            case 'site_url':

                $siteUrl = site_url();
                $siteUrl = '<a href="' . $siteUrl . '">' . $siteUrl . '</a>';

                return $siteUrl;

                break;

            case 'site_name':

                $siteName = get_bloginfo( 'name' );

                return $siteName;

                break;

            case 'unsubscribe':

                $siteUrl = trailingslashit( site_url() );
                $unsubscribeEndpoint = apply_filters( 'acr_unsubscribe_endpoint', 'acr-unsubscribe' );
                $unsubscribeLink = $siteUrl . $unsubscribeEndpoint . '/?email=' . $email . '&token=' . md5( $cartID );

                // Used to track email schedules used
                if( isset( $_REQUEST[ 'acr_email_schedule_id' ] ) )
                    $unsubscribeLink = untrailingslashit( $unsubscribeLink ) . '&ref=' . $_REQUEST[ 'acr_email_schedule_id' ];

                $uLink = '<a href="' . $unsubscribeLink . '">Unsubscribe</a>';

                return $uLink;

                break;

            do_action( 'acr_get_cart_info', $cartID, $getInfo );

        }
	}

    /**
     * Set filter wp_mail "From" Header
     *
     * @return string
     * @since 1.0.0
     */
    public function acrWPMailFrom() {

        $fromEmail = trim( get_option( 'woocommerce_email_from_address' ) );

        return apply_filters( 'acr_email_from_email' , $fromEmail );

    }

    /**
     * Set filter wp_mail "From Name" Header
     *
     * @return string
     * @since 1.0.0
     */
    public function acrWPMailFromName() {

        $wcFromName = trim( get_option( 'woocommerce_email_from_name' ) );

        return apply_filters( 'acr_email_from_name' , $wcFromName );

    }

    /**
     * Construct email headers.
     *
     * @param string $fromName
     * @param string $fromEmail
     *
     * @return array
     * @since 1.0.0
     */
    public function acrConstructEmailHeader( $fromName , $fromEmail ) {

        $headers[] = 'From: ' . $fromName . ' < ' . $fromEmail . ' > ';

        $headers[] = apply_filters( 'acr_email_content_type', 'Content-Type: text/html;' );

        $headers[] = apply_filters( 'acr_email_charset', 'charset=UTF-8' );

        return apply_filters( 'acr_email_header', $headers );

    }

    /**
     * Add inline styling to email templates.
     *
     * @param string $itemRowClass
     * @param string $column
     *
     * @return array
     * @since 1.1.0
     */
    public static function acrItemRowEmailCSS( $itemRowClass, $column ){

        // Add styling on Email Templates column
        if( isset( $_REQUEST[ 'acr_email_send' ] ) ){

            $classes = explode( ' ', $itemRowClass );

            if( $column == 'name' && ( in_array( 'composited_item', $classes ) || in_array( 'bundled_item', $classes ) ) ){
                return 'padding: 0px !important; padding-left: 32px !important';
            }else{
                return 'padding: 0px !important;';
            }
        }

        return;
    }
}
