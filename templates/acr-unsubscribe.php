<?php
/**
 * Unsubscribe page
 *
 * Override this template by copying it to yourtheme/woocommerce/acr-unsubscribe.php
 *
 * @author      Rymera Web Co
 * @package     Advanced-Cart-Recovery/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header(); ?>
	
	<?php do_action( 'acr_before_unsubscribed' ); ?>

	<header class="entry-header">
		<h1 class="entry-title"><?php _e( 'Unsubscribed!', 'advanced-cart-recovery' ); ?> </h1>
	</header>

	<div class="entry-content">
		<p><?php _e( 'Thank you. You have successfully been removed from any future communications about abandoned shopping carts.', 'advanced-cart-recovery' ); ?> </p>
	</div>

	<?php do_action( 'acr_after_unsubscribed' ); ?>

<?php get_footer();

