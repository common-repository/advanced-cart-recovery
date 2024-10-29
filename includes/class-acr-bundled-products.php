<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Bundled_Products {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

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
    public function acrBundlesTableItemClass( $classname, $item, $itemKey ) {

        if ( class_exists( 'WC_PB_Order' ) ){

            $wcPBOrder = WC_PB_Order::instance();

            return $wcPBOrder->html_order_item_class( $classname, $item );

        }

        return $classname;

    }

    /**
     * Check if the item is bundled parent.
     *
     * @param array $item
     *
     * @return bool
     * @since 1.1.0
     */
    public static function acrCheckIfBundledParent( $item ){

        if( isset( $item[ 'item_meta' ][ '_bundled_items' ][ 0 ] ) && ! isset( $item[ 'item_meta' ][ '_bundled_by' ][ 0 ] ) &&
            ! isset( $item[ 'composite_item'] ) )
            return true;
        else
            return false;

    }

    /**
     * Set http $_REQUEST variables to be used before adding bundled items to cart.
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
    public function acrSetBundledRequestVariables( $item, $productID, $quantity, $product ){

        $bundledItems   = $product->get_bundled_items();
        $stamp          = maybe_unserialize( $item[ 'item_meta' ][ '_stamp' ][ 0 ] );

        if( ! empty( $stamp ) && ! empty( $bundledItems ) ){

            foreach ( $bundledItems as $bundleID => $bundleData ){

                if( array_key_exists( $bundleID, $stamp ) ){

                    if( isset( $stamp[ $bundleID ][ 'optional_selected' ] ) && 
                        $stamp[ $bundleID ][ 'optional_selected' ] == 'yes' ){
                        $_POST[ 'bundle_selected_optional_' . $bundleID ] = 'yes';
                    }

                    $_POST[ 'bundle_quantity_' . $bundleID ] = (int) $stamp[ $bundleID ][ 'quantity' ];

                    if( $bundleData->product->product_type == 'variable' ){

                        $_POST[ 'bundle_variation_id_' . $bundleID ] = (int) $stamp[ $bundleID ][ 'variation_id' ];

                        if( ! empty( $stamp[ $bundleID ][ 'attributes' ] ) ){

                            foreach ( $stamp[ $bundleID ][ 'attributes' ] as $attr => $val ){
                                $_POST[ 'bundle_' . $attr . '_' . $bundleID ] = $val;
                            }
                        }
                    }
                }else{

                    // There's a posibility that the bundled items maybe updated (added or removed items) so we only need to display 
                    // those data that are stored in post meta cart object and don't include the new updated items or attributes
                    $_POST[ 'bundle_quantity_' . $bundleID ] = (int) 0;

                }
            }
        }
    }
}