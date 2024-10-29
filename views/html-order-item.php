<?php
/**
 * Shows an order item.
 * A copy from woocommerce\includes\admin\meta-boxes\views\html-order-item.php
 *
 * @var object $item The item being displayed
 * @var int $itemID The id of the item being displayed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product  		= $order->get_product_from_item( $item );
$productLink 	= $product ? admin_url( 'post.php?post=' . absint( ACR_Functions::acrGetProductID( $product ) ) . '&action=edit' ) : '';
$thumbnail     	= $product ? apply_filters( 'acr_order_item_thumbnail', $product->get_image( array( 50, 50 ) ), $itemID, $item ) : '';
$itemTotal   	= ( isset( $item[ 'line_total' ] ) ) ? esc_attr( wc_format_localized_price( $item[ 'line_total' ] ) ) : '';
$itemSubtotal 	= ( isset( $item[ 'line_subtotal' ] ) ) ? esc_attr( wc_format_localized_price( $item[ 'line_subtotal' ] ) ) : '';
$itemRowClass   = apply_filters( 'acr_order_item_class', 'cart_item', $item, $order ); ?>

<tr class="item <?php echo $itemRowClass; ?>" data-order_item_id="<?php echo $itemID; ?>">
	<td class="thumb" style="<?php echo ACR_Emails::acrItemRowEmailCSS( $itemRowClass, 'thumb' ); ?>"><?php
			echo '<div class="acr-order-item-thumbnail">' . wp_kses_post( $thumbnail ) . '</div>'; ?>
	</td>
	<td class="name" data-sort-value="<?php echo esc_attr( $item['name'] ); ?>" style="<?php echo ACR_Emails::acrItemRowEmailCSS( $itemRowClass, 'name' ); ?>"><?php
		echo $productLink ? '<a href="' . esc_url( $productLink ) . '" class="acr-order-item-name">' .  esc_html( $item['name'] ) . '</a>' : '<div class="class="acr-order-item-name"">' . esc_html( $item['name'] ) . '</div>';

		if ( $product && $product->get_sku() ) {
			echo '<div class="acr-order-item-sku"><b>' . __( 'SKU:', 'advanced-cart-recovery' ) . '</b> ' . esc_html( $product->get_sku() ) . '</div>';
		}

		if ( ! empty( $item[ 'variation_id' ] ) ) {
			echo '<div class="acr-order-item-variation"><b>' . __( 'Variation ID:', 'advanced-cart-recovery' ) . '</b> ';
			if ( ! empty( $item[ 'variation_id' ] ) && 'product_variation' === get_post_type( $item[ 'variation_id' ] ) ) {
				echo esc_html( $item[ 'variation_id' ] );
			} elseif ( ! empty( $item[ 'variation_id' ] ) ) {
				echo esc_html( $item[ 'variation_id' ] ) . ' (' . __( 'No longer exists', 'advanced-cart-recovery' ) . ')';
			}
			echo '</div>';
		}

		do_action( 'acr_before_order_itemmeta', $itemID, $item, $product );

		include( 'html-order-item-meta.php' );

		do_action( 'acr_after_order_itemmeta', $itemID, $item, $product ) ?>

	</td>

	<?php do_action( 'acr_order_item_values', $product, $item, absint( $itemID ) ); ?>

	<td class="item_cost" width="8%" data-sort-value="<?php echo esc_attr( $order->get_item_subtotal( $item, false, true ) ); ?>" style="<?php echo ACR_Emails::acrItemRowEmailCSS( $itemRowClass, 'item_cost' ); ?>">
		<div class="view"><?php

			if ( isset( $item[ 'line_total' ] ) ) {
				echo wc_price( $order->get_item_total( $item, false, true ), array( 'currency' => ACR_Functions::acrGetOrderCurrency( $order ) ) );

				if ( isset( $item[ 'line_subtotal' ] ) && $item[ 'line_subtotal' ] != $item[ 'line_total' ] ) {
					echo '<span class="acr-order-item-discount">-' . wc_price( wc_format_decimal( $order->get_item_subtotal( $item, false, false ) - $order->get_item_total( $item, false, false ), '' ), array( 'currency' => ACR_Functions::acrGetOrderCurrency( $order ) ) ) . '</span>';
				}
			} ?>
		</div>
	</td>
	<td class="quantity" width="8%" style="<?php echo ACR_Emails::acrItemRowEmailCSS( $itemRowClass, 'quantity' ); ?>">
		<div class="view"><?php

			echo '<small class="times">&times;</small> ' . ( isset( $item[ 'qty' ] ) ? esc_html( $item[ 'qty' ] ) : '1' );

			if ( $refunded_qty = $order->get_qty_refunded_for_item( $itemID ) ) {
				echo '<small class="refunded">' . ( $refunded_qty * -1 ) . '</small>';
			} ?>

		</div>
	</td>
	<td class="line_cost" width="8%" data-sort-value="<?php echo esc_attr( isset( $item['line_total'] ) ? $item['line_total'] : '' ); ?>" style="<?php echo ACR_Emails::acrItemRowEmailCSS( $itemRowClass, 'line_cost' ); ?>">
		<div class="view"><?php
			if ( isset( $item[ 'line_total' ] ) ) {
				echo wc_price( $item[ 'line_total' ], array( 'currency' => ACR_Functions::acrGetOrderCurrency( $order ) ) );
			}

			if ( isset( $item[ 'line_subtotal' ] ) && $item[ 'line_subtotal' ] !== $item[ 'line_total' ] ) {
				echo '<span class="acr-order-item-discount">-' . wc_price( wc_format_decimal( $item[ 'line_subtotal' ] - $item[ 'line_total' ], '' ), array( 'currency' => ACR_Functions::acrGetOrderCurrency( $order ) ) ) . '</span>';
			}

			if ( $refunded = $order->get_total_refunded_for_item( $itemID ) ) {
				echo '<small class="refunded">' . wc_price( $refunded, array( 'currency' => ACR_Functions::acrGetOrderCurrency( $order ) ) ) . '</small>';
			} ?>
		</div>
	</td>
</tr>
