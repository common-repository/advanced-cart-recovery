<?php
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

global $wpdb;
$cleanup = get_option( 'acr_clean_plugin_options' );

if( isset( $cleanup ) && $cleanup == 'yes' ){

    include_once( 'advanced-cart-recovery.options.php' );
    include_once( 'includes/class-acr-cron.php' );

    $res = $wpdb->get_results( "SELECT p.ID FROM $wpdb->posts p WHERE p.post_type = 'recovered-cart'", ARRAY_A );
    $acrCron = ACR_Cron::getInstance();
    $cartIDs = array();

    // Remove cron events
    foreach ( $res as $post ) {

        $cartID = $post[ 'ID' ];
        $cartIDs[] = $cartID;
        $abandonedCartArgs = get_post_meta( $cartID, ACR_ABANDONED_CART_CRON_ARGS, true );

        // Remove pending abandoned cart cron
        if( ! empty( $abandonedCartArgs ) ){
            wp_clear_scheduled_hook( ACR_ABANDONED_CART_CRON, $abandonedCartArgs );
        }

        // Remove pending email and cancelled cart cron
        $acrCron->acrUnscheduleCronEventsByCartID( $cartID );

    }

    // Delete options.
    $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'acr\_%';" );

    // Delete Entries
    if( ! empty( $cartIDs ) ){

        $cartIDs = implode( ', ', $cartIDs );
        $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE $wpdb->postmeta.post_id IN (" . $cartIDs . ")" );
        $wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN (" . $cartIDs . ")" );

    }
    
    // Clear any cached data that has been removed
    wp_cache_flush();

}