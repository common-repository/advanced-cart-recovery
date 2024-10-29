<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// This is where you set various options affecting the plugin

// Path Constants ======================================================================================================
define( 'ACR_URL',                 				plugins_url() . '/advanced-cart-recovery/' );
define( 'ACR_DIR',                 				plugin_dir_path( __FILE__ ) );
define( 'ACR_WC_THEME_URL',                		get_stylesheet_directory_uri() . '/woocommerce/' );
define( 'ACR_WC_THEME_DIR',             		get_stylesheet_directory() . '/woocommerce/' );
define( 'ACR_CSS_URL',        					ACR_URL . 'css/' );
define( 'ACR_CSS_DIR',        					ACR_DIR . 'css/' );
define( 'ACR_IMAGES_URL',     					ACR_URL . 'images/' );
define( 'ACR_IMAGES_DIR',     					ACR_DIR . 'images/' );
define( 'ACR_INCLUDES_URL',   					ACR_URL . 'includes/' );
define( 'ACR_INCLUDES_DIR',   					ACR_DIR . 'includes/' );
define( 'ACR_JS_URL',         					ACR_URL . 'js/' );
define( 'ACR_JS_DIR',         					ACR_DIR . 'js/' );
define( 'ACR_LOGS_URL',       					ACR_URL . 'logs/' );
define( 'ACR_LOGS_DIR',       					ACR_DIR . 'logs/' );
define( 'ACR_VIEWS_URL',     					ACR_URL . 'views/' );
define( 'ACR_VIEWS_DIR',      					ACR_DIR . 'views/' );

// Plugin Constants =====================================================================================================
define( 'ACR_CPT_NAME',      					'recovered-cart' ); // ACR custom post type name
define( 'ACR_ABANDONED_CART_CRON', 				'acr_abandoned_cart_cron' ); // Abandoned cart cron name
define( 'ACR_EMAIL_SENDER_CRON', 				'acr_email_sender_cron' ); // Email sender cron name
define( 'ACR_CANCELLED_CART_CRON', 				'acr_cancelled_cart_cron' ); // Cancelled cart cron name
define( 'ACR_ABANDONED_CART_CRON_ARGS', 		'_' . ACR_ABANDONED_CART_CRON . '_args' ); // Abandoned cart cron args name
define( 'ACR_EMAIL_SENDER_CRON_ARGS', 			'_' . ACR_EMAIL_SENDER_CRON . '_args' ); // Email sender cron args name
define( 'ACR_CANCELLED_CART_CRON_ARGS', 		'_' . ACR_CANCELLED_CART_CRON . '_args' ); // Cancelled cart cron args name
define( 'ACR_BLACKLIST_EMAILS_OPTION', 			'acr_blacklist_emails_option' ); // Contains listings of all the blacklisted emails
define( 'ACR_EMAIL_SCHEDULES_OPTION', 			'acr_email_schedules_option' ); // Contains all scheduled email templates.
define( 'ACR_ACTIVATION_CODE_TRIGGERED', 		'acr_activation_code_triggered' ); // Check if activation code is triggered.
define( 'ACR_PLUGIN_VERSION', 		            'acr_plugin_version' ); // Plugin version saved to database
