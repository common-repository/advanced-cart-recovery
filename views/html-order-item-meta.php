<?php
	global $wpdb;

	if ( $metadata = ACR_Functions::acrGetLineItemMeta( $itemID , $order ) ) {
		foreach ( $metadata as $meta ) {

			$meta_key   = is_object( $meta ) ? $meta->key : $meta[ 'meta_key' ];
			$meta_value = is_object( $meta ) ? $meta->value : $meta[ 'meta_value' ];

			// Skip hidden core fields
			if ( in_array( $meta_key , apply_filters( 'woocommerce_hidden_order_itemmeta', array(
				'_qty',
				'_tax_class',
				'_product_id',
				'_variation_id',
				'_line_subtotal',
				'_line_subtotal_tax',
				'_line_total',
				'_line_tax',
				'method_id',
				'cost'
			) ) ) ) {
				continue;
			}

			// Skip serialised meta
			if ( is_serialized( $meta_value ) ) {
				continue;
			}

			// Get attribute data
			if ( taxonomy_exists( wc_sanitize_taxonomy_name( $meta_key ) ) ) {
				$term               	= get_term_by( 'slug', $meta_value, wc_sanitize_taxonomy_name( $meta_key ) );
				$meta_key			   	= wc_attribute_label( wc_sanitize_taxonomy_name( $meta_key ) );
				$meta_value			 	= isset( $term->name ) ? $term->name : $meta_value;
			} else {
				$meta_key   = wc_attribute_label( $meta_key , $product );
			}

			$var = '<div class="acr-order-item-variation"><b>' . ucwords( wp_kses_post( rawurldecode( $meta_key ) ) ) . '</b>: ';
				if ( ! filter_var( $meta_value, FILTER_VALIDATE_URL ) == false ) {
					$var .= '<a target="_blank" href="'. $meta_value .'">'. basename( $meta_value ) . '</a>';
				}else{
					$var .= ucwords( wp_kses_post( make_clickable( rawurldecode( $meta_value ) ) ) );
				}
			$var .= '</div>';

			echo $var;
		}
	}
?>
