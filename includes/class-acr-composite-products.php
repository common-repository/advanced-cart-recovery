<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Composite_Products {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Changes the tr class of composited items in all templates to allow their styling.
     *
     * @param string $classname
     * @param array $item
     * @param string $itemKey
     *
     * @return string
     * @since 1.1.0
     */
    public function acrCompositeTableItemClass( $classname, $item, $itemKey ) {

        if ( class_exists( 'WC_CP_Order' ) ){

            $wcCPOrder = WC_CP_Order::instance();
            return $wcCPOrder->html_order_item_class( $classname, $item );

        }

        return $classname;
    }

    /**
     * Check if the item is a composite parent.
     *
     * @param array $item
     *
     * @return boolean
     * @since 1.1.0
     */
    public static function acrCheckIfCompositeParent( $item ){

        if( isset( $item[ 'item_meta' ][ '_composite_children' ][ 0 ] ) && ! isset( $item[ 'item_meta' ][ '_composite_parent' ][ 0 ] ) )
            return true;
        else 
            return false;

    }

    /**
     * Set http $_REQUEST variables to be used before adding composite component items to cart.
     *
     * @param array $item
     * @param int $productID
     * @param int $quantity
     * @param object $product
     *
     * @return bool
     * @since 1.1.0
     * @since 1.3.0 On Product Bundles v1.5.0, they changed it to accept $_POST or $_GET method.
     *              Their Note: We will not rely on $_REQUEST because checkbox names may not exist in $_POST but they may well exist in $_GET, for instance when editing a bundle from the cart.
     */
    public function acrSetCompositeRequestVariables( $item, $productID, $quantity, $product ){

        $componentsToAdd        = array();
        $componentsToAddQty     = array();
        $componentsVariationID  = array();
        $compositeData          = maybe_unserialize( $item[ 'item_meta' ][ '_composite_data' ][ 0 ] );

        if( ! empty( $compositeData ) ){

            foreach ( $compositeData as $componentID => $composite ){

                $componentsToAdd[ $componentID ]       = $composite[ 'product_id' ]; // The actual product id
                $componentsToAddQty[ $componentID ]    = (int) ( $composite[ 'quantity' ] != $quantity ) ? $composite[ 'quantity' ] * $quantity : $quantity; // Component product qty
                
                if( isset( $composite[ 'type' ] ) && $composite[ 'type' ] == 'variable' ){

                    $componentsVariationID[ $componentID ] = ! empty( $composite[ 'variation_id' ] ) ? $composite[ 'variation_id' ] : 0;
                    
                    if( ! empty( $composite[ 'attributes' ] ) && ! empty( $composite[ 'variation_id' ] ) ){
                     
                        $component           = wc_get_product( $composite[ 'product_id' ] );
                        $attributes          = $component->get_variation_attributes();

                        if( ! empty( $attributes ) ){
                            foreach ( $attributes as $variationName => $variation ){

                                $attrVal = '';
                                if( ! empty( $composite[ 'attributes' ][ 'attribute_' . $variationName ] ) )
                                    $attrVal = $composite[ 'attributes' ][ 'attribute_' . $variationName ];

                                $_POST[ 'wccp_attribute_' . $variationName ][ $componentID ] = $attrVal;

                            }
                        }
                    }

                }elseif( isset( $composite[ 'type' ] ) && $composite[ 'type' ] == 'bundle' ){

                    $bundleProduct  = wc_get_product( $composite[ 'product_id' ] );
                    $bundledItems   = $bundleProduct->get_bundled_items();

                    if( ! empty( $composite[ 'stamp' ] ) && ! empty( $bundledItems ) ){

                        foreach ( $bundledItems as $bundleID => $bundleData ){

                            if( array_key_exists( $bundleID, $composite[ 'stamp' ] ) ){
                                
                                if( isset( $composite[ 'stamp' ][ $bundleID ][ 'optional_selected' ] ) && 
                                    $composite[ 'stamp' ][ $bundleID ][ 'optional_selected' ] == 'yes' ){
                                    $_POST[ 'component_' . $componentID . '_bundle_selected_optional_' . $bundleID ] = 'yes';
                                }

                                $_POST[ 'component_' . $componentID . '_bundle_quantity_' . $bundleID ] = (int) $composite[ 'stamp' ][ $bundleID ][ 'quantity' ];
                                
                                if( $bundleData->product->product_type == 'variable' ){

                                    $_POST[ 'component_' . $componentID . '_bundle_variation_id_' . $bundleID ] = (int) $composite[ 'stamp' ][ $bundleID ][ 'variation_id' ];

                                    if( ! empty( $composite[ 'stamp' ][ $bundleID ][ 'attributes' ] ) ){

                                        foreach ( $composite[ 'stamp' ][ $bundleID ][ 'attributes' ] as $attr => $val ){
                                            $_POST[ 'component_' . $componentID . '_bundle_' . $attr . '_' . $bundleID ] = $val;
                                        }
                                    }
                                }
                            }else{

                                // There's a posibility that the bundled items maybe updated (added or removed items) so we only need to display 
                                // those data that are stored in post meta cart object and don't include the new updated items or attributes
                                $_POST[ 'component_' . $componentID . '_bundle_quantity_' . $bundleID ] = (int) 0;

                            }
                        }
                    }
                }
            }
        }

        // Pre-fill the request (required to add composite components correctly)
        if ( ! empty( $componentsToAdd ) && ! empty( $componentsToAddQty ) ){
            
            $_POST[ 'wccp_component_selection' ] = $componentsToAdd;
            $_POST[ 'wccp_component_quantity' ]  = $componentsToAddQty;

            if( ! empty( $componentsVariationID ) )
                $_POST[ 'wccp_variation_id' ] = $componentsVariationID;

        }
    }
}
