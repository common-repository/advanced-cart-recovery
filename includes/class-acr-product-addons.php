<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class ACR_Product_Addons {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Constructor
     */
    function __construct() {

        // Add item data to the cart
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'acrAddCartItemData' ), 10, 3 );

    }

    /**
     * Check if WooCommerce Product Add-on plugin is installed.
     *
     * @return bool
     * @since 1.2.0
     */
    public function acrAddonPluginiActiveCheck(){

        if( class_exists( 'Product_Addon_Cart' ) && in_array( 'woocommerce-product-addons/woocommerce-product-addons.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
            return true;
        else 
            return false;

    }

    /**
     * Get addon data from ACR postmeta
     *
     * @param int $cartID
     * @param int $productID
     *
     * @return mixed
     * @since 1.2.0
     */
    public function acrGetAddonsDataFromCart( $cartID, $productID ){

        if( ! is_null( $cartID ) ){
            $cartContents = get_post_meta( $cartID, '_acr_cart_contents', true );
            if( ! empty( $cartContents ) ){
                foreach ( $cartContents as $key => $data ) {
                    if( $productID == $data[ 'product_id' ] || $productID == $data[ 'variation_id' ] ){
                        if( ! empty( $data[ 'addons' ] ) ){
                            return $data[ 'addons' ];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Merge addon data with the present product addons.
     *
     * @param array $cartItemData
     * @param int $variationID
     * @param int $productID
     *
     * @return array
     * @since 1.2.0
     */
    public function acrAddCartItemData( $cartItemData, $productID, $variationID ){

        if( $this->acrAddonPluginiActiveCheck() ){
            $cartID = null;
            if( isset( $_REQUEST[ 'addons' ] ) && $_REQUEST[ 'addons' ] == true && isset( $_REQUEST[ 'cart_id' ] ) ){
                $cartID = $_REQUEST[ 'cart_id' ];
            }

            if ( empty( $cartItemData[ 'addons' ] ) ) {
                $cartItemData[ 'addons' ] = array();
            }

            $addons = $this->acrGetAddonsDataFromCart( $cartID, $productID );
            if( ! is_null( $addons ) )
                $cartItemData[ 'addons' ] = array_merge( $cartItemData[ 'addons' ], $addons );
        }

        return $cartItemData;

    }
}