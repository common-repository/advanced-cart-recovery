<?php
/**
 * Plugin Name:       Advanced Cart Recovery
 * Plugin URI:        https://marketingsuiteplugin.com
 * Description:       Automates the recovery of abandoned shopping carts in WooCommerce.
 * Version:           1.3.2
 * Author:            Rymera Web Co
 * Author URI:        http://rymera.com.au/
 * Text Domain:       advanced-cart-recovery
 */

/**
 * Register Global Deactivation Hook.
 * Codebase that must be run on plugin deactivation whether or not dependencies are present.
 * Necessary to prevent activation code from being executed more than once.
 *
 * @since 1.3.0
 */
function acrGlobalPluginDeactivate( $networkWide ) {

	global $wpdb;

	// check if it is a multisite network
	if ( is_multisite() ) {

		// check if the plugin has been deactivated on the network or on a single site
		if ( $networkWide ) {

			// get ids of all sites
			$blogIDs = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );

			foreach ( $blogIDs as $blogID ) {

				switch_to_blog( $blogID );
				delete_option( 'acr_activation_code_triggered' );

			}

			restore_current_blog();

		} else {

			// deactivated on a single site, in a multi-site
			delete_option( 'acr_activation_code_triggered' );

		}

	} else {

		// deactivated on a single site
		delete_option( 'acr_activation_code_triggered' );

	}

}

register_deactivation_hook( __FILE__ , 'acrGlobalPluginDeactivate' );

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	// Include Necessary Files
	require_once ( 'advanced-cart-recovery.options.php' );
	require_once ( 'advanced-cart-recovery.plugin.php' );

	// Get Instance of Main Plugin Class
	$advanced_cart_recovery = Advanced_Cart_Recovery::getInstance();
	$GLOBALS[ 'advanced_cart_recovery' ] = $advanced_cart_recovery;

	// Initialize Plugin
    add_action( 'init' , array( $advanced_cart_recovery , 'acrInitialize' ) );

	// Register Activation Hook
	register_activation_hook( __FILE__ , array( $advanced_cart_recovery, 'acrActivate' ) );

	// Register Deactivation Hook
	register_deactivation_hook( __FILE__ , array( $advanced_cart_recovery, 'acrDeactivate' ) );

	//  Register AJAX Call Handlers
	add_action( 'init', array( $advanced_cart_recovery, 'acrRegisterAJAXCallHandlers' ) );

	// Execute plugin initialization ( plugin activation ) on every newly created site in a multi site set up
    add_action( 'wpmu_new_blog', array( $advanced_cart_recovery, 'acrMultisiteInit' ), 10, 6 );

	// Load Backend CSS and JS
	add_action( 'admin_enqueue_scripts', array( $advanced_cart_recovery, 'acrLoadBackEndStylesAndScripts' ) );

	// Load Frontend CSS and JS
	add_action( 'wp_enqueue_scripts', array( $advanced_cart_recovery, 'acrLoadFrontEndStylesAndScripts' ) );

	// Register Settings Page
	add_filter( 'woocommerce_get_settings_pages', array( $advanced_cart_recovery, 'acrSettings' ) );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ) , array( $advanced_cart_recovery , 'acrAddPluginListingCustomActionLinks' ) , 10 , 2 );

	// Register Recovered Carts CPT
	add_action( 'init', array( $advanced_cart_recovery, 'acrRegisterRecoveredCartsCPT' ) );

	// Restore cart contents for abandoned orders
	add_action( 'template_redirect', array( $advanced_cart_recovery , 'acrRestoreCartContentsForAbandonedOrders' ) );
	add_action( 'acr_after_cart_restore', array( $advanced_cart_recovery, 'acrAddNoticeAfterCartRestore' ) );

	// Add new meta boxes
	add_action( 'add_meta_boxes', array( $advanced_cart_recovery , 'acrMetaBoxes' ) );

	// Remove Submit meta box
	add_action( 'add_meta_boxes', array( $advanced_cart_recovery , 'acrRemoveMetaBoxes' ) );

	// Add custom column to Recovered Cart CPT
	add_filter( 'manage_recovered-cart_posts_columns', array( $advanced_cart_recovery, 'acrSetNewAdvancedCartRecoveryColumn' ), 10, 1 );
	add_action( 'manage_recovered-cart_posts_custom_column' , array( $advanced_cart_recovery, 'acrAdvancedCartRecoveryNewColumns' ), 10, 2 );

	// Set cron event to track for abandoned carts after checkout
	add_action( 'woocommerce_checkout_update_order_meta', array( $advanced_cart_recovery, 'acrOnPlaceOrder' ), 10, 2 );

	// Make cart abandoned if scheduled time is met
	add_action( ACR_ABANDONED_CART_CRON, array( $advanced_cart_recovery, 'acrAbandonedCart' ), 10, 4 );

	// Filter duplicate email address and email sender - Using wp cron job
	add_action( ACR_EMAIL_SENDER_CRON, array( $advanced_cart_recovery, 'acrEmailSender' ), 20, 3 );

	// Register custom post status for ACR post type
	add_action( 'init', array( $advanced_cart_recovery , 'acrCreateCustomPostStatus' ), 20 );

	// Checks if the cart is recovered
	add_action( 'acr_after_cart_restore', array( $advanced_cart_recovery , 'acrSetRecoveryProcessFlag' ) );
	add_action( 'woocommerce_order_status_completed', array( $advanced_cart_recovery, 'acrRecoveredCart' ), 10, 1 );
	add_action( 'woocommerce_order_status_processing', array( $advanced_cart_recovery, 'acrRecoveredCart' ), 10, 1 );

	// Manually run cron jobs
	add_filter( 'admin_init', array( $advanced_cart_recovery, 'acrRunCronManually' ) );
	add_action( 'admin_notices', array( $advanced_cart_recovery, 'acrAddAdminNotices' ), 100 );

	// Add our custom Endpoint for our unsubscribe page
	add_action( 'init', array( $advanced_cart_recovery, 'acrEndpointInit' ) );
	add_action( 'template_redirect', array( $advanced_cart_recovery, 'acrCatchEndpointVars' ) );
	add_filter( 'request', array( $advanced_cart_recovery, 'acrEndpointFilterRequest' ), 10, 1 );
	add_filter( 'query_vars', array( $advanced_cart_recovery, 'acrAddQueryVars' ), 10, 1 );

	// Check if Not Recovered carts are inactive for nth number of days, if so change status to cancelled.
	add_action( ACR_CANCELLED_CART_CRON, array( $advanced_cart_recovery, 'acrCancelledCartChecker' ), 10, 3 );

    // Schedule abandoned forever cron event after every or last email.
    add_action( 'acr_send_email', array( $advanced_cart_recovery, 'acrScheduleAbandonedForeverEvent' ), 10, 5 );

    // Display notice if DISABLE_WP_CRON is enabled
    add_action( 'admin_notices', array( $advanced_cart_recovery, 'acrDisplayNoticeIfCronIsDisabled' ) );
    add_action( 'admin_init', array( $advanced_cart_recovery, 'acrDismissWPCronNotice' ) );

    // Load Plug-ins Text Domain
	add_action( 'plugins_loaded', array( $advanced_cart_recovery, 'acrLoadPluginTextdomain' ) );

	// Check if ACR CPT edit screen is loaded at the back end
	add_action( 'current_screen', array( $advanced_cart_recovery, 'acrCheckScreen' ) );

	// Cart Manager
	add_action( 'trashed_post', array( $advanced_cart_recovery, 'acrTrashACRCPTEntry' ) );
	add_action( 'untrashed_post', array( $advanced_cart_recovery, 'acrRestoreACRCPTEntry' ) );
	add_action( 'before_delete_post', array( $advanced_cart_recovery, 'acrDeleteACRCPTEntry' ) );

	// Check if Product Bundles plugin is activated
	if( in_array( 'woocommerce-product-bundles/woocommerce-product-bundles.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){

		// Display Bundled products properly on the ordered items meta box
		add_filter( 'acr_order_item_class', array( $advanced_cart_recovery, 'acrBundlesTableItemClass' ), 10, 3 );

	}

	// Check if Composite Products plugin is activated
	if( in_array( 'woocommerce-composite-products/woocommerce-composite-products.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){

		// Display Composite products properly on the ordered items meta box
		add_filter( 'acr_order_item_class', array( $advanced_cart_recovery, 'acrCompositeTableItemClass' ), 10, 3 );

	}

	// If a user is deleted, remove any entries associated with it
	add_action( 'delete_user', array( $advanced_cart_recovery, 'acrDeleteUser' ) );

	// Add filtering of abandoned carts by its status
	add_action( 'restrict_manage_posts', array( $advanced_cart_recovery, 'acrFilterListingsByStatus' ) );
	add_filter( 'parse_query', array( $advanced_cart_recovery, 'acrFilterListingsByStatusQuery' ) );

	add_action( 'woocommerce_order_status_completed' , array( $advanced_cart_recovery , 'acrDeleteCartsEventsOnOrderSuccess' ) , 10 , 1 );
	add_action( 'woocommerce_order_status_processing' , array( $advanced_cart_recovery , 'acrDeleteCartsEventsOnOrderSuccess' ) , 10 , 1 );

}else{


    /**
     * Display admin notice that WooCommerce is prerequisite.
     *
     * @since 1.2.0
     * @since 1.3.0 If WooCommerce plugin is not yet active then we don't activate Advanced Cart Recovery yet. This is to ensure the default options is set on activation hook.
     */
    function acrAdminNotices() {

        $adminNoticeMsg = '';
        $pluginKey 		= 'woocommerce';
        $pluginName  	= 'WooCommerce';
        $pluginFile     = 'woocommerce/woocommerce.php';
        $sptFile        = trailingslashit( WP_PLUGIN_DIR ) . plugin_basename( $pluginFile );

        $sptInstallText = '<a href="' . wp_nonce_url( 'update.php?action=install-plugin&plugin=' . $pluginKey, 'install-plugin_' . $pluginKey ) . '">' . __( 'Click here to install from WordPress.org repo &rarr;', 'advanced-cart-recovery' ) . '</a>';
        if ( file_exists( $sptFile ) )
            $sptInstallText = '<a href="' . wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $pluginFile . '&amp;plugin_status=all&amp;s', 'activate-plugin_' . $pluginFile ) . '" title="' . __( 'Activate this plugin', 'advanced-cart-recovery' ) . '" class="edit">' . __( 'Click here to activate &rarr;', 'advanced-cart-recovery' ) . '</a>';

        $adminNoticeMsg .= sprintf( __( '<br/>Unable to activate the plugin. Please ensure you have the <a href="%1$s" target="_blank">%2$s</a> plugin installed and activated.<br/>', 'advanced-cart-recovery' ), 'http://wordpress.org/plugins/' . $pluginKey. '/', $pluginName );
        $adminNoticeMsg .= $sptInstallText . '<br/>'; ?>

        <div class="error">
            <p>
                <?php _e( '<b>Advanced Cart Recovery</b> plugin missing dependency.<br/>', 'advanced-cart-recovery' ); ?>
                <?php echo $adminNoticeMsg; ?>
            </p>
        </div><?php

    }

    add_action( 'admin_notices', 'acrAdminNotices' );

}
