<?php
/**
 * WooCommerce Bento Events Controller
 *
 * @package BentoHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooCommerce_Bento_Events extends Bento_Events_Controller {

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        add_action(
            'woocommerce_thankyou',
            function( $order_id ) {
                $order = wc_get_order( $order_id );

                $user_id = 0 !== $order->get_user_id() ? $order->get_user_id() : null;

                self::send_event(
                    $user_id,
                    '$_test_OrderPlaced',
                    $order->get_billing_email(),
                    self::get_order_details( $order )
                );
            }
        );
    }

    /**
     * Prepare the order details.
     *
     * @return void
     */
    private static function get_order_details( $order_id ) {
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $details = array(
            'cart'  => array(
                'items' => self::get_order_items( $order ),
            ),
        );

        return $details;
    }

    /**
     * Prepare the order items.
     *
     * @return void
     */
    private static function get_order_items( $order ) {
        $base_currency = get_woocommerce_currency();

        $items = array();

        foreach ( $order->get_items() as $item ) {
            $product = $item->get_product();

            $items[] = array(
                'shop_base_currency'    => $base_currency,
                'product_id'            => $product->get_id(),
                'product_name'          => $product->get_name(),
                'product_permalink'     => $product->get_permalink(),
                'product_price'         => $product->get_price(),
                'product_regular_price' => $product->get_regular_price(),
                'product_sale_price'    => $product->get_sale_price(),
                'product_sku'           => $product->get_sku(),
                'quantity'              => $item->get_quantity(),
                'line_total'            => $order->get_line_total( $item, true, true ),
                'line_tax'              => $order->get_line_tax( $item ),
                'line_subtotal'         => $order->get_line_subtotal( $item, true, true ),
                'line_subtotal_tax'     => $order->get_line_subtotal( $item, true, true ) - $order->get_line_subtotal( $item, false, true ),
            );
        }

        return $items;
    }
}

new WooCommerce_Bento_Events();