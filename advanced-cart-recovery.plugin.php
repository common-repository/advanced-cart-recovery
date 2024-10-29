<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( !class_exists( 'Advanced_Cart_Recovery' ) ) {


    if( !class_exists( 'ACR_Cart_Recovery' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-cart-recovery.php' );

    if( !class_exists( 'ACR_Custom_Meta_Boxes' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-custom-meta-boxes.php' );

    if( !class_exists( 'ACR_Emails' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-emails.php' );

    if( !class_exists( 'ACR_Cron' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-cron.php' );

    if( !class_exists( 'ACR_AJAX' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-ajax.php' );

    if( !class_exists( 'ACR_Endpoint' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-endpoint.php' );

    if( !class_exists( 'ACR_Cart_Manager' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-cart-manager.php' );

    if( !class_exists( 'ACR_Functions' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-functions.php' );

    if( !class_exists( 'ACR_Bundled_Products' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-bundled-products.php' );

    if( !class_exists( 'ACR_Composite_Products' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-composite-products.php' );

    if( !class_exists( 'ACR_Product_Addons' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-product-addons.php' );

    if( !class_exists( 'ACR_Guided_Tour' ) )
        require_once ( ACR_INCLUDES_DIR . 'class-acr-guided-tour.php' );

    /**
     * Class Advanced_Cart_Recovery
     */
    class Advanced_Cart_Recovery {

        /*
	     |--------------------------------------------------------------------------------------------------------------
	     | Class Members
	     |--------------------------------------------------------------------------------------------------------------
	     */

        private static $_instance;

        private $_acr_cart_recovery;
        private $_acr_custom_meta_boxes;
        private $_acr_emails;
        private $_acr_cron;
        private $_acr_ajax;
        private $_acr_endpoint;
        private $_acr_cart_manager;
        private $_acr_bundled_products;
        private $_acr_composite_products;
        private $_acr_product_addons;
        private $_acr_guided_tour;

        const VERSION = '1.3.2';

        /*
	     |--------------------------------------------------------------------------------------------------------------
	     | Mesc Functions
	     |--------------------------------------------------------------------------------------------------------------
	     */

        /**
         * Class constructor.
         *
         * @since 1.0.0
         */
        public function __construct() {

            $this->_acr_cart_recovery = ACR_Cart_Recovery::getInstance();
            $this->_acr_custom_meta_boxes = ACR_Custom_Meta_Boxes::getInstance();
            $this->_acr_emails = ACR_Emails::getInstance();
            $this->_acr_cron = ACR_Cron::getInstance();
            $this->_acr_ajax = ACR_AJAX::getInstance();
            $this->_acr_endpoint = ACR_Endpoint::getInstance();
            $this->_acr_cart_manager = ACR_Cart_Manager::getInstance();
            $this->_acr_bundled_products = ACR_Bundled_Products::getInstance();
            $this->_acr_composite_products = ACR_Composite_Products::getInstance();
            $this->_acr_product_addons = ACR_Product_Addons::getInstance();
            $this->_acr_guided_tour = ACR_Guided_Tour::getInstance();

        }

        /**
         * Create plugin instance.
         *
         * @return Advanced_Cart_Recovery
         * @since 1.0.0
         */
        public static function getInstance() {

            if( !self::$_instance instanceof self )
                self::$_instance = new self;

            return self::$_instance;

        }

        /*
	     |--------------------------------------------------------------------------------------------------------------
	     | Bootstrap/Shutdown Functions
	     |--------------------------------------------------------------------------------------------------------------
	     */

        /**
         * Plugin initializaton.
         *
         * @since 1.3.0
         */
        public function acrInitialize(){

            if ( ! function_exists( 'is_plugin_active_for_network' ) )
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

            $networkWide = is_plugin_active_for_network( 'advanced-cart-recovery/advanced-cart-recovery.bootstrap.php' );
            if ( get_option( ACR_ACTIVATION_CODE_TRIGGERED, false ) !== 'yes' )
                $this->acrActivate( $networkWide );

        }

        /**
         * Method to initialize a newly created site in a multi site set up.
         *
         * @param $blogID
         * @param $userID
         * @param $domain
         * @param $path
         * @param $siteID
         * @param $meta
         *
         * @since 1.3.0
         */
        public function acrMultisiteInit( $blogID, $userID, $domain, $path, $siteID, $meta ) {

            if ( is_plugin_active_for_network( 'advanced-cart-recovery/advanced-cart-recovery.bootstrap.php' ) ) {

                switch_to_blog( $blogID );
                $this->acrInitDefaultData( $blogID );
                restore_current_blog();

            }
        }

        /**
         * Plugin activation hook callback.
         *
         * @param $networkWide
         *
         * @since 1.0.0
         * @since 1.3.0 Added multisite compatibility.
         */
        public function acrActivate( $networkWide ) {

            global $wpdb;

            if( is_multisite() ){

                if( $networkWide ){

                    // get ids of all sites
                    $blogIDs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                    foreach( $blogIDs as $blogID ){

                        switch_to_blog( $blogID );
                        $this->acrInitDefaultData( $blogID );

                    }

                    restore_current_blog();

                }else{

                    // activated on a single site, in a multi-site
                    $this->acrInitDefaultData( $wpdb->blogid );

                }

            }else{

                // activated on a single site
                $this->acrInitDefaultData( $wpdb->blogid );

            }

        }

        /**
         * Actual function that houses the code to execute on plugin activation.
         *
         * @param $blogID
         *
         * @since 1.3.0
         */
        private function acrInitDefaultData( $blogID ) {

            // Initialize CPT
            $this->_acr_cart_recovery->acrRegisterRecoveredCartsCPT();

            // Flush rewrite rules after CPT has been initialized
            flush_rewrite_rules();

            // Set default values for status considered abandoned
            $getSetAbandonedStatus = get_option( 'acr_general_status_considered_abandoned' );

            if( empty( $getSetAbandonedStatus ) ){

                $getSetAbandonedStatus = array( 'wc-pending', 'wc-cancelled' );
                update_option( 'acr_general_status_considered_abandoned', $getSetAbandonedStatus );

            }

            // Set default values for status considered completed
            $getSetCompletedStatus = get_option( 'acr_general_status_considered_completed' );

            if( empty( $getSetCompletedStatus ) ){

                $getSetCompletedStatus = array( 'wc-completed', 'wc-processing' );
                update_option( 'acr_general_status_considered_completed', $getSetCompletedStatus );

            }

            // Set default value for cart abandoned time
            $getSetAbandonedTime = get_option( 'acr_general_cart_abandoned_time' );
            if( empty( $getSetAbandonedTime ) )
                update_option( 'acr_general_cart_abandoned_time', '6' );

            // Set default value for abandoned cart time considered as cancelled
            $getTimeConsideredCancelled = get_option( 'acr_general_time_considered_cancelled' );
            if( empty( $getTimeConsideredCancelled ) )
                update_option( 'acr_email_days_after_order_abandoned', '7' );

            $acrEmailSchedules = get_option( ACR_EMAIL_SCHEDULES_OPTION );

            if ( ! is_array( $acrEmailSchedules ) && empty( $acrEmailSchedules[ 'initial' ] ) ){

                $acrEmails = ACR_Emails::getInstance();
                $acrEmailSchedules = array();

                $acrEmailSchedules[ 'initial' ][ 'wrap' ] = 'yes';
                $acrEmailSchedules[ 'initial' ][ 'heading_text' ] = '';
                $acrEmailSchedules[ 'initial' ][ 'subject' ] = stripslashes( $acrEmails->acrDefaultTemplate[ 'subject' ] );
                $acrEmailSchedules[ 'initial' ][ 'days_after_abandoned' ] = 1;
                $acrEmailSchedules[ 'initial' ][ 'content' ] = stripslashes( $acrEmails->acrDefaultTemplate[ 'body' ] );

                update_option( ACR_EMAIL_SCHEDULES_OPTION, $acrEmailSchedules );
            }

            // Guided Tour
            if ( get_option( 'acr_guided_tour_status' ) === false )
                update_option( 'acr_guided_tour_status', 'open' );

            // update blackist emails data
            $this->_updateBlacklistEmailsDataOnUpdate();

            // save plugin version to database
            update_option( ACR_PLUGIN_VERSION , self::VERSION );

            // Flush rewrite rules after CPT has been initialized
            flush_rewrite_rules();

            update_option( ACR_ACTIVATION_CODE_TRIGGERED, 'yes' );

        }

        /**
         * Plugin deactivation hook callback.
         *
         * @param $blogID
         *
         * @since 1.0.0
         * @since 1.3.0 Added multisite compatibility.
         */
        public function acrDeactivate( $networkWide ) {

            global $wpdb;

            // check if it is a multisite network
            if ( is_multisite() ) {

                // check if the plugin has been activated on the network or on a single site
                if ( $networkWide ) {

                    // get ids of all sites
                    $blogIDs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

                    foreach ( $blogIDs as $blogID ) {

                        switch_to_blog( $blogID );
                        $this->acrDeactivateActions( $blogID );

                    }

                    restore_current_blog();

                } else {

                    // activated on a single site, in a multi-site
                    $this->acrDeactivateActions( $wpdb->blogid );

                }

            } else {

                // activated on a single site
                $this->acrDeactivateActions( $wpdb->blogid );

            }

        }

        /**
         * Plugin deactivation hook callback.
         *
         * @param $blogID
         *
         * @since 1.3.0
         */
        private function acrDeactivateActions( $blogID ) {

            flush_rewrite_rules();

        }

        /**
         * Update saved data if version is not the same
         *
         * @since 1.3.1
         */
        private function _updateBlacklistEmailsDataOnUpdate() {

            $version_on_db = get_option( ACR_PLUGIN_VERSION );

            if ( isset( $version_on_db ) && version_compare( $version_on_db , '1.3.1' ) >= 0 )
                return;

            // convert blacklist emails date to timestamp
            $acrBlacklistedEmails = get_option( ACR_BLACKLIST_EMAILS_OPTION , array() );

            if ( ! empty( $acrBlacklistedEmails ) ) {

                foreach ( $acrBlacklistedEmails as $email => $data ) {

                    if ( is_numeric( $data[ 'date' ] ) )
                        continue;

                    $acrBlacklistedEmails[ $email ][ 'date' ] = strtotime( $data[ 'date' ] );
                }

                update_option( ACR_BLACKLIST_EMAILS_OPTION , $acrBlacklistedEmails );
            }

        }

        /*
	    |---------------------------------------------------------------------------------------------------------------
	    | Admin Functions
	    |---------------------------------------------------------------------------------------------------------------
	    */

        /**
         * Load admin or back-end related styles and scripts.
         *
         * @since 1.0.0
         */
        public function acrLoadBackEndStylesAndScripts() {

            global $post;
            $screen = get_current_screen();

            // Help Section, Debug mode On
            if( isset( $_GET[ 'section' ] ) && isset( $_GET[ 'debug' ] ) && $_GET[ 'section' ] == 'acr_settings_help_section' && $_GET[ 'debug' ] == true ){

                // Styles
                wp_enqueue_style( 'acr_backend_css', ACR_CSS_URL . 'acr-backend.css' , array(), self::VERSION, 'all' );

            }

            // Edit Screen
            if( is_admin() && $screen->post_type == ACR_CPT_NAME ){

                // Styles
                wp_enqueue_style( 'acr_backend_css', ACR_CSS_URL . 'acr-backend.css', array(), self::VERSION, 'all' );

                // Scripts
                wp_enqueue_script( 'clipboardjs', ACR_JS_URL . 'lib/clipboardjs/clipboard.min.js', array( 'jquery' ), self::VERSION, false );

            }

            // Settings
            if( ( isset( $_GET[ 'tab' ] ) && $_GET[ 'tab' ] == 'acr_settings' ) ){

                // Scripts
                wp_enqueue_script( 'acr_settings_js', ACR_JS_URL . 'app/acr-settings.js', array( 'jquery' ), self::VERSION, true );

            }

            // Blacklist Section
            if( isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'acr_blacklist_emails_section' ){

                // Styles
                wp_enqueue_style( 'acr_blacklist_emails_option', ACR_CSS_URL . 'acr-blacklist-emails-option.css', array(), self::VERSION, 'all' );
                wp_enqueue_style( 'acr_toastr_css', ACR_JS_URL . 'lib/toastr/toastr.min.css', array(), self::VERSION, 'all' );

                // Scripts
                wp_enqueue_script( 'acr_toastr_js', ACR_JS_URL . 'lib/toastr/toastr.min.js', array( 'jquery' ), self::VERSION );
                wp_enqueue_script( 'acr_actions_js', ACR_JS_URL . 'app/modules/acrActions.js', array( 'jquery' ), self::VERSION );
                wp_enqueue_script( 'acr_backend_ajax_services_js', ACR_JS_URL . 'app/modules/acrBackendAJAXServices.js', array( 'jquery' ), self::VERSION, true );
                wp_enqueue_script( 'acr_settings_blacklist_js', ACR_JS_URL . 'app/acr-settings-blacklist.js', array( 'jquery' ), self::VERSION, true );
                wp_localize_script( 'acr_settings_blacklist_js',
                                    'acr_blacklist_control_vars',
                                    array(
                                        'empty_fields_error_message'    =>  __( 'The following fields have empty values.', 'advanced-cart-recovery' ),
                                        'error_email_format'            =>  __( 'Please enter the correct email format.', 'advanced-cart-recovery' ),
                                        'success_save_message'          =>  __( 'Email Successfully Added', 'advanced-cart-recovery' ),
                                        'failed_save_message'           =>  __( 'Failed To Save Email', 'advanced-cart-recovery' ),
                                        'success_edit_message'          =>  __( 'Email Successfully Updated', 'advanced-cart-recovery' ),
                                        'failed_edit_message'           =>  __( 'Failed To Update Email', 'advanced-cart-recovery' ),
                                        'failed_retrieve_message'       =>  __( 'Failed Retrieve Email Data', 'advanced-cart-recovery' ),
                                        'confirm_box_message'           =>  __( 'Clicking OK will remove the email from the list', 'advanced-cart-recovery' ),
                                        'no_emails_message'             =>  __( 'No Emails Found', 'advanced-cart-recovery' ),
                                        'failed_delete_message'         =>  __( 'Failed To Delete Email', 'advanced-cart-recovery' ),
                                        'success_delete_message'        =>  __( 'Email Deleted Successfully', 'advanced-cart-recovery' ),
                                        'email_empty'                   =>  __( 'Field Email', 'advanced-cart-recovery' ),

                                    ));

            }

            // Email Schedules Section
            if( isset( $_GET[ 'section' ] ) && $_GET[ 'section' ] == 'acr_settings_email_schedules' ){

                // Styles
                wp_enqueue_style( 'acr_email_schedules_css', ACR_CSS_URL . 'acr-email-schedules.css', array(), self::VERSION, 'all' );
                wp_enqueue_style( 'acr_toastr_css', ACR_JS_URL . 'lib/toastr/toastr.min.css', array(), self::VERSION, 'all' );

                // Scripts
                wp_enqueue_script( 'jquery-ui-dialog' );
                wp_enqueue_script( 'acr_actions_js', ACR_JS_URL . 'app/modules/acrActions.js', array( 'jquery' ), self::VERSION );
                wp_enqueue_script( 'acr_toastr_js', ACR_JS_URL . 'lib/toastr/toastr.min.js', array( 'jquery' ), self::VERSION );
                wp_enqueue_script( 'acr_backend_ajax_services_js', ACR_JS_URL . 'app/modules/acrBackendAJAXServices.js', array( 'jquery' ), self::VERSION, true );
                wp_enqueue_script( 'acr_settings_email_schedule_js', ACR_JS_URL . 'app/acr-settings-email-schedules.js', array( 'jquery' ), self::VERSION, true );
                wp_localize_script( 'acr_settings_email_schedule_js',
                                    'acr_email_schedule_control_vars',
                                    array(
                                        'empty_fields_error_message'    =>  __( 'Please fill the form properly. <br/>Some fields have invalid values.', 'advanced-cart-recovery' ),
                                        'success_save_message'          =>  __( 'Schedule Successfully Added', 'advanced-cart-recovery' ),
                                        'failed_save_message'           =>  __( 'Failed To Save Schedule', 'advanced-cart-recovery' ),
                                        'success_edit_message'          =>  __( 'Schedule Successfully Updated', 'advanced-cart-recovery' ),
                                        'failed_edit_message'           =>  __( 'Failed To Update Schedule', 'advanced-cart-recovery' ),
                                        'failed_retrieve_message'       =>  __( 'Failed Retrieve Schedule Data', 'advanced-cart-recovery' ),
                                        'confirm_box_message'           =>  __( 'Clicking OK will remove the schedule from the list', 'advanced-cart-recovery' ),
                                        'no_schedules_message'          =>  __( 'No Schedules Found', 'advanced-cart-recovery' ),
                                        'failed_delete_message'         =>  __( 'Failed To Delete Schedule', 'advanced-cart-recovery' ),
                                        'success_delete_message'        =>  __( 'Schedule Deleted Successfully', 'advanced-cart-recovery' ),
                                        'failed_view'                   =>  __( 'Failed To View', 'advanced-cart-recovery' ),
                                        'subject_empty'                 =>  __( '"Subject" field is empty.', 'advanced-cart-recovery' ),
                                        'days_empty'                    =>  __( '"Days After Abandoned" field is empty.', 'advanced-cart-recovery' ),
                                        'days_positive_only'            =>  __( '"Days After Abandoned" only accepts positive value.', 'advanced-cart-recovery' ),
                                        'days_duplicate_values'         =>  __( 'Duplicate "Days After Abandoned" value.', 'advanced-cart-recovery' ),
                                        'content_empty'                 =>  __( '"Content" is empty.', 'advanced-cart-recovery' ),
                                        'heading_text_empty'            =>  __( '"Heading Text" is empty.', 'advanced-cart-recovery' ),

                                    ));
            }

            // Guided Tours
            if ( get_option( 'acr_guided_tour_status', false ) == 'open' && array_key_exists( $screen->id, $this->_acr_guided_tour->acrGetScreens() ) ) {

                wp_enqueue_style( 'acr_plugin-guided-tour_css', ACR_CSS_URL . 'acr-guided-tour.css', array( 'wp-pointer' ), self::VERSION, 'all' );
                wp_enqueue_script( 'acr_plugin-guided-tour_js', ACR_JS_URL . 'app/acr-guided-tour.js', array( 'wp-pointer', 'thickbox' ), self::VERSION, true );

                wp_localize_script( 'acr_plugin-guided-tour_js',
                                    'acr_guided_tour_params',
                                    array(
                                        'actions' => array( 'close_tour' => 'acrCloseGuidedTour' ),
                                        'nonces'  => array( 'close_tour' => wp_create_nonce( 'acr-close-guided-tour' ) ),
                                        'screen'  => $this->_acr_guided_tour->acrGetCurrentScreen(),
                                        'height'  => 640,
                                        'width'   => 640,
                                        'texts'   => array(
                                                        'btn_prev_tour'  => __( 'Previous', 'advanced-cart-recovery' ),
                                                        'btn_next_tour'  => __( 'Next', 'advanced-cart-recovery' ),
                                                        'btn_close_tour' => __( 'Close', 'advanced-cart-recovery' ),
                                                        'btn_start_tour' => __( 'Start Tour', 'advanced-cart-recovery' )
                                                    ),
                                        'urls'    => array( 'ajax' => admin_url( 'admin-ajax.php' ) ),
                                        'post'    => isset( $post ) && isset( $post->ID ) ? $post->ID : 0
                                    ));

            }
        }

        /**
         * Load front-end related styles and scripts.
         *
         * @since 1.0.0
         */
        public function acrLoadFrontEndStylesAndScripts() {
            // Please only load styles and scripts on the right place and on the right time
        }



        /*
	    |---------------------------------------------------------------------------------------------------------------
	    | AJAX Callbacks
	    |---------------------------------------------------------------------------------------------------------------
	    */

        /**
         * Register AJAX callbacks.
         *
         * @since 1.0.0
         */
        public function acrRegisterAJAXCallHandlers() {

            add_action( 'wp_ajax_acrAddEmailToBlacklist', array( self::getInstance(), 'acrAddEmailToBlacklist' ), 10, 2 );
            add_action( 'wp_ajax_acrDeleteEmailFromBlacklist', array( self::getInstance(), 'acrDeleteEmailFromBlacklist' ) );

            add_action( 'wp_ajax_acrViewEmailSchedule', array( self::getInstance(), 'acrViewEmailSchedule' ) );
            add_action( 'wp_ajax_acrUpdateEmailSchedule', array( self::getInstance(), 'acrUpdateEmailSchedule' ), 10, 2 );

            // Plugin Guided Tours
            add_action( 'wp_ajax_acrCloseGuidedTour', array( $this->_acr_guided_tour, 'acrCloseGuidedTour' ) );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Load Plugin Textdomain
        |---------------------------------------------------------------------------------------------------------------
        */
        /**
         * Load plugin Textdomain
         *
         * @since 1.0.0
         */
        public function acrLoadPluginTextdomain() {

            load_plugin_textdomain( 'advanced-cart-recovery', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Settings
        |---------------------------------------------------------------------------------------------------------------
        */


        /**
         * Settings Config for ACR
         *
         * @return array
         * @since 1.0.0
         */
        public function acrSettings( $settings ){

            $settings[] = include( ACR_INCLUDES_DIR . 'class-acr-settings.php' );

            return $settings;

        }

        /**
         * Add custom action links for the plugin in the plugin listings
         *
         * @param string $links
         * @param string $file
         *
         * @return array
         * @since 1.0.0
         */
        public function acrAddPluginListingCustomActionLinks( $links , $file ){

            $help     = '<a href="https://marketingsuiteplugin.com/knowledge-base/advanced-cart-recovery/?utm_source=ACR&utm_medium=Settings%20Help&utm_campaign=ACR">' . __( 'Help' , 'advanced-cart-recovery' ) . '</a>';
            $settings = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=acr_settings' ) . '">' . __( 'Settings' , 'advanced-cart-recovery' ) . '</a>';
            array_unshift( $links , $help , $settings );

            return $links;

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Cart Recovery Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Register custom post status for ACR post type
         *
         * @since 1.0.0
         */
        public function acrCreateCustomPostStatus(){

            $this->_acr_cart_recovery->acrCreateCustomPostStatus();

        }

        /**
         * Used to register new Cart Recovery CPT.
         *
         * @since 1.0.0
         */
        public function acrRegisterRecoveredCartsCPT(){

            $this->_acr_cart_recovery->acrRegisterRecoveredCartsCPT();

        }

        /**
         * Create new column on the Recovered Cart CPT
         *
         * @param array $columns
         *
         * @return array
         * @since 1.0.0
         */
        public function acrSetNewAdvancedCartRecoveryColumn( $columns ){

            return $this->_acr_cart_recovery->acrSetNewAdvancedCartRecoveryColumn( $columns );

        }

        /**
         * Sets the row value of the new column
         *
         * @param string $columns
         * @param int $postID
         *
         * @since 1.0.0
         */
        public function acrAdvancedCartRecoveryNewColumns( $columns, $postID ){

            $this->_acr_cart_recovery->acrAdvancedCartRecoveryNewColumns( $columns, $postID );

        }

        /**
         * Grab a copy of cancelled orders into recovered-cart CPT
         *
         * @since 1.0.0
         */
        public function acrCancelUnpaidOrders(){

            $this->_acr_cart_recovery->acrCancelUnpaidOrders();

        }

        /**
         * Restore cart contents for abandoned orders.
         *
         * @since 1.0.0
         */
        public function acrRestoreCartContentsForAbandonedOrders(){

            $this->_acr_cart_recovery->acrRestoreCartContentsForAbandonedOrders();

        }

        /**
         * Add a notice after the cart is successfully restored.
         *
         * @since 1.0.0
         */
        public function acrAddNoticeAfterCartRestore(){

            $this->_acr_cart_recovery->acrAddNoticeAfterCartRestore();

        }

        /**
         * Set cron event to track for abandoned carts after checkout
         *
         * @param int $orderID
         * @param mixed posted
         *
         * @since 1.0.0
         */
        public function acrOnPlaceOrder( $orderID, $posted ){

            $this->_acr_cart_recovery->acrOnPlaceOrder( $orderID, $posted );

        }

        /**
         * Make the cart abandoned.
         *
         * @param int $userID
         * @param string $userEmail
         * @param int $orderID
         * @param string $orderStatus
         *
         * @since 1.0.0
         */
        public function acrAbandonedCart( $userID, $userEmail, $orderID, $orderStatus = null ){

            $this->_acr_cart_recovery->acrAbandonedCart( $userID, $userEmail, $orderID, $orderStatus );

        }

        /**
         * Change post status to recovered
         *
         * @param int $orderID
         *
         * @since 1.0.0
         */
        public function acrRecoveredCart( $orderID ){

            $this->_acr_cart_recovery->acrRecoveredCart( $orderID );

        }

        /**
         * Set session flag for our recovery process
         *
         * @param int $cartID
         *
         * @since 1.0.0
         */
        public function acrSetRecoveryProcessFlag( $cartID ){

            $this->_acr_cart_recovery->acrSetRecoveryProcessFlag( $cartID );

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

            $this->_acr_cart_recovery->acrCancelledCartChecker( $cartID, $cartStatus, $email );

        }

        /**
         * Display notice if DISABLE_WP_CRON is enabled
         *
         * @since 1.0.0
         * @since 1.2.0 Option to dismiss the notice.
         */
        public function acrDisplayNoticeIfCronIsDisabled(){

            $this->_acr_cart_recovery->acrDisplayNoticeIfCronIsDisabled();

        }

        /**
         * If a user is deleted, remove any entries associated with it
         *
         * @since 1.1.0
         */
        public function acrDeleteUser( $userID ){

            $this->_acr_cart_recovery->acrDeleteUser( $userID );

        }

        /**
         * Dismiss wp cron notice.
         *
         * @since 1.2.0
         */
        public function acrDismissWPCronNotice() {

            if ( isset( $_GET[ 'acr_dismiss_wp_cron_notice' ] ) && '1' == $_GET[ 'acr_dismiss_wp_cron_notice' ] )
                update_option( 'acr_dismiss_wp_cron_notice', true );

        }

        /**
         * If a customer places a successful order after the failed order, this function will delete all abandoned cart entries
         * that has not yet sent an email, and also remove all scheduled abandoned cart cron events for the customer
         *
         * @param int    $orderID
         *
         * @since 1.3.1
         */
        public function acrDeleteCartsEventsOnOrderSuccess( $orderID ) {

            $this->_acr_cart_recovery->acrDeleteCartsEventsOnOrderSuccess( $orderID );
        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Custom Meta Boxes Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Add new meta boxes
         *
         * @since 1.0.0
         */
        public function acrMetaBoxes(){

            $this->_acr_custom_meta_boxes->acrMetaBoxes();

        }

        /**
         * Remove meta boxes
         *
         * @since 1.0.0
         */
        public function acrRemoveMetaBoxes(){

            $this->_acr_custom_meta_boxes->acrRemoveMetaBoxes();

        }

        /**
         * Check if ACR CPT edit screen is loaded at the back end
         *
         * @since 1.0.0
         */
        public function acrCheckScreen(){

            $this->_acr_custom_meta_boxes->acrCheckScreen();

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Emails Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Function to send the email to the customer
         *
         * @param int $cartID
         * @param array $acrStatus
         * @param string $email
         *
         * @since 1.0.0
         */
        public function acrEmailSender( $cartID, $acrStatus, $email ){

            $this->_acr_emails->acrEmailSender( $cartID, $acrStatus, $email );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Cron Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Set custom cron schedule
         *
         * @since 1.0.0
         */
        public function acrRunCronManually(){

            $this->_acr_cron->acrRunCronManually();

        }

        /**
         * Add admin notices for manual cron
         *
         * @since 1.0.0
         */
        public function acrAddAdminNotices(){

            $this->_acr_cron->acrAddAdminNotices();

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
        public function acrScheduleAbandonedForeverEvent( $cartID, $scheduleID, $acrStatus, $email, $isSent, $response = array() ){

            if( ! empty( $acrStatus ) )
                $this->_acr_cron->acrScheduleAbandonedForeverEvent( $cartID, $scheduleID, $acrStatus, $email, $isSent, $response );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | AJAX Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Blacklist email address.
         *
         * @param string $email
         *
         * @since 1.0.0
         */
        public function acrAddEmailToBlacklist( $email = null, $reason = null ){

            $this->_acr_ajax->acrAddEmailToBlacklist( $email, $reason );

        }

        /**
         * Remove email address from blacklist.
         *
         * @param string $email
         *
         * @since 1.0.0
         */
        public function acrDeleteEmailFromBlacklist( $email = null ){

            $this->_acr_ajax->acrDeleteEmailFromBlacklist( $email );

        }

        /**
         * Option to view the email schedule details
         *
         * @param string $key
         *
         * @since 1.0.0
         */
        public function acrViewEmailSchedule( $key = null ){

            $this->_acr_ajax->acrViewEmailSchedule( $key );

        }

        /**
         * Update the email schedule
         *
         * @param string $key
         * @param array $emailFields
         *
         * @since 1.0.0
         */
        public function acrUpdateEmailSchedule( $key = null, $emailFields = null ){

            $this->_acr_ajax->acrUpdateEmailSchedule( $key, $emailFields = null );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Endpoint Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Initialize Endpoint
         *
         * @since 1.0.0
         */
        public function acrEndpointInit(){

            $this->_acr_endpoint->acrEndpointInit();

        }

        /**
         * Catch endpoint vars.
         *
         * @since 1.0.0
         */
        public function acrCatchEndpointVars(){

            $this->_acr_endpoint->acrCatchEndpointVars();

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

            return $this->_acr_endpoint->acrEndpointFilterRequest( $vars );

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

            return $this->_acr_endpoint->acrAddQueryVars( $vars );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Cart Manager Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Unset all cron events when ACR CPT entry is trashed.
         *
         * @param int $postID
         *
         * @since 1.0.0
         */
        public function acrTrashACRCPTEntry( $postID ){

            $this->_acr_cart_manager->acrTrashACRCPTEntry( $postID );

        }

        /**
         * Set cron events when ACR CPT entry is untrashed.
         *
         * @param int $postID
         *
         * @since 1.0.0
         */
        public function acrRestoreACRCPTEntry( $postID ){

            $this->_acr_cart_manager->acrRestoreACRCPTEntry( $postID );

        }

        /**
         * Before the entry is delete, we must remove any cron events attached.
         *
         * @param int $postID
         *
         * @since 1.0.0
         */
        public function acrDeleteACRCPTEntry( $postID ){

            $this->_acr_cart_manager->acrDeleteACRCPTEntry( $postID );

        }

        /**
         * Add filtering of abandoned carts by its status
         *
         * @since 1.2.0
         */
        public function acrFilterListingsByStatus(){

            $this->_acr_cart_manager->acrFilterListingsByStatus();

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

            return $this->_acr_cart_manager->acrFilterListingsByStatusQuery( $query );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Bundled Products Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Change the tr class of bundled items to allow their styling.
         *
         * @param string $classname
         * @param array $item
         * @param string $itemKey
         *
         * @return string
         * @since 1.1.0
         */
        public function acrBundlesTableItemClass( $classname, $item, $itemKey ){

            return $this->_acr_bundled_products->acrBundlesTableItemClass( $classname, $item, $itemKey );

        }

        /*
        |---------------------------------------------------------------------------------------------------------------
        | Composite Products Class
        |---------------------------------------------------------------------------------------------------------------
        */

        /**
         * Change the tr class of bundled items to allow their styling.
         *
         * @param string $classname
         * @param array $item
         * @param string $itemKey
         *
         * @return string
         * @since 1.1.0
         */
        public function acrCompositeTableItemClass( $classname, $item, $itemKey ){

            return $this->_acr_composite_products->acrCompositeTableItemClass( $classname, $item, $itemKey );

        }
    }
}
