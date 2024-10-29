<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Guided_Tour {
    
    private static $_instance;
    private $urls;
    private $screens;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * ACR_Guided_Tour constructor.
     *
     * @since 1.3.0
     */
    private function __construct() {

        $this->urls = apply_filters( 'acr_guided_tour_pages' , array(
            'plugin-listing'    => admin_url( 'plugins.php' ),
            'orders-listing'    => admin_url( 'edit.php?post_type=shop_order' ),
            'acr-settings'      => admin_url( 'admin.php?page=wc-settings&tab=acr_settings' ),
            'acr-schedules'     => admin_url( 'admin.php?page=wc-settings&tab=acr_settings&section=acr_settings_email_schedules' ),
            'acr-listing'       => admin_url( 'edit.php?post_type=recovered-cart' )
        ));

        $this->screens = apply_filters( 'acr_guided_tours' , array(
            'plugins' => array(
                'elem'  => '#menu-plugins .menu-top',
                'html'  => __( '<h3>Welcome to Advanced Cart Recovery!</h3>
                                <p>Would you like to go on a guided tour of the plugin? Takes less than 30 seconds.</p>' , 'advanced-cart-recovery' ),
                'prev'  => null,
                'next'  => $this->urls[ 'orders-listing' ],
                'edge'  => 'left',
                'align' => 'left'
            ),
            'edit-shop_order' => array(
                'elem'  => '#toplevel_page_woocommerce ul li a.current',
                'html'  => __( '<h3>Advanced Cart Recovery is a tool used to recover orders that are abandoned before the purchase was completed.</h3>
                                <p>It can help you recover orders that were cancelled or left in pending payment (or any other status that is not desirable) by sending automated emails that encourage them to come back to finalise their purchase.</p>
                                <p>The simple act of asking your customers to come back is often all that is needed to recover the order and add that revenue back to your bottom line.</p>' ),
                'prev'  => $this->urls[ 'plugin-listing' ],
                'next'  => $this->urls[ 'acr-settings' ],
                'edge'  => 'left',
                'align' => 'left'
            ),
            'woocommerce_page_wc-settings' => array(
                'general' => array(
                    'elem'  => 'ul.subsubsub li a.current',//'.nav-tab-active',
                    'html'  => __( '<h3>This is the General settings area.</h3>
                                    <p>There\'s a number of options here that change the way Advanced Cart Recovery behaves in relation to determining when an order is considered abandoned by the customer, how long to wait for a response to your campaign to get them back, and more.</p>
                                    <p>Next we\'ll look at how to set the actual emails that go out.</p>' , 'advanced-cart-recovery' ),
                    'prev'  => $this->urls[ 'orders-listing' ],
                    'next'  => $this->urls[ 'acr-schedules' ],
                    'edge'  => 'top',
                    'align' => 'left'
                ),
                'schedules' => array(
                    'elem'  => 'ul.subsubsub li a.current',//'.nav-tab-active',
                    'html'  => __( '<h3>This is the Email Schedules.</h3>
                                    <p>From here you configure the emails that are used to encourage customers to come back to your store..</p>
                                    <p>On the free version you can only have one email, but the Premium add-on give you the option of adding multiple emails to form a longer schedule.</p>
                                    <p>Not everyone responds on the first try and sometimes it can be helpful to offer a coupon or other incentive when all other attempts to get them back have failed.</p>
                                    <p>The emails you set here are sent out on a schedule based on the number of days after the order was abandoned by the customer.</p>', 'advanced-cart-recovery' ),
                    'prev'  => $this->urls[ 'acr-settings' ],
                    'next'  => $this->urls[ 'acr-listing' ],
                    'edge'  => 'top',
                    'align' => 'left'
                ),
                'end' => array(
                    'elem'  => 'a.nav-tab-active', //'.nav-tab-active',
                    'html'  => sprintf( __( '<h3>This concludes the tour. You are now ready to setup your settings and email schedules!</h3>
                                         <p>Want to unlock all of the extra features? The Premium add-on is packed full of useful features and amazing reports so you can see how your abandoned cart recovery efforts are going.</p>
                                         <p>Plus, we\'re adding new features all the time!</p>
                                         <p><a href="%1$s" target="_blank" class="button button-primary">Check out the Premium version now &rarr;</a></p>' , 'advanced-cart-recovery' ) , 'https://marketingsuiteplugin.com/product/advanced-cart-recovery/?utm_source=ACR&utm_medium=Settings%20Banner&utm_campaign=ACR' ),
                    'prev'  => $this->urls[ 'acr-listing' ],
                    'next'  => null,
                    'edge'  => 'top',
                    'align' => 'left'
                )
            ),
            'edit-recovered-cart' => array(
                'elem'  => '#toplevel_page_woocommerce ul li a.current',
                'html'  => __( '<h3>This is the list of all of the currently Abandoned Carts that are being recovered.</h3>
                                <p>When an order in the defined statuses (eg. Cancelled or Pending Payment) are left that way for the period defined in the settings (default is 6 hours), they will be considered abandoned by the customer and an entry will be created here to track the order recovery efforts.</p>
                                <p>You can drill down into particular abandoned carts here to get more details about the customer and the order.</p>', 'advanced-cart-recovery' ),
                'prev'  => $this->urls[ 'acr-schedules' ],
                'next'  => $this->urls[ 'acr-settings' ],
                'edge'  => 'left',
                'align' => 'left'
            )
        ));

    }

    /**
     * Get current screen.
     * 
     * @since 1.3.0
     */
    public function acrGetCurrentScreen() {

        $screen = get_current_screen();
        $lastPage = $_SERVER['HTTP_REFERER'];

        if ( ! empty( $this->screens[ $screen->id ] ) ){

            if( $screen->id == 'woocommerce_page_wc-settings' && ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'acr_settings' ) ){

                if ( ! isset( $_GET[ 'section' ] ) && $lastPage == admin_url( 'edit.php?post_type=recovered-cart' ) )
                    return $this->screens[ $screen->id ][ 'end' ];

                elseif( isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'acr_settings_email_schedules' )
                    return $this->screens[ $screen->id ][ 'schedules' ];

                else return $this->screens[ $screen->id ][ 'general' ];

            }

            return $this->screens[ $screen->id ];

        }

        return false;

    }

    /**
     * Get screens with registered guide.
     * 
     * @since 1.3.0
     */
    public function acrGetScreens() {

        return $this->screens;

    }

    /**
     * Close initial guided tour.
     * 
     * @since 1.3.0
     */
    public function acrCloseGuidedTour() {

        if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {

            if ( !check_ajax_referer( 'acr-close-guided-tour', 'nonce', false ) )
                wp_die( __( 'Security Check Failed', 'advanced-cart-recovery' ) );

            update_option( 'acr_guided_tour_status', 'close' );

            wp_send_json_success();

        } else
            wp_die( __( 'Invalid AJAX Call', 'advanced-cart-recovery' ) );
        
    }
}