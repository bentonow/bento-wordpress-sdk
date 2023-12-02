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

                // If the order has an associated user.
                $user_id = self::maybe_get_user_id_from_order( $order );

                // Preare the order details.
                $details = self::prepare_order_event_details( $order );

                // Add the order value.
                $details['value'] = array(
                    'currency' => $order->get_currency(),
                    'amount'   => $order->get_total(),
                );

                self::send_event(
                    $user_id,
                    '$OrderPlaced',
                    $order->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_order_refunded',
            function( $order_id, $refund_id ) {
                $order = wc_get_order( $order_id );

                // If the order has an associated user.
                $user_id = self::maybe_get_user_id_from_order( $order );

                // Preare the order details.
                $details = self::prepare_order_event_details( $order );

                // Get the refund.
                $refund = wc_get_order( $refund_id );

                // Add the refund value.
                $details['value'] = array(
                    'currency' => $refund->get_currency(),
                    'amount'   => $refund->get_total(),
                );

                self::send_event(
                    $user_id,
                    '$OrderRefunded',
                    $order->get_billing_email(),
                    $details
                );
            },
            10,
            2
        );

        add_action(
            'woocommerce_order_status_cancelled',
            function( $order_id ) {
                $order = wc_get_order( $order_id );

                // If the order has an associated user.
                $user_id = self::maybe_get_user_id_from_order( $order );

                // Preare the order details.
                $details = self::prepare_order_event_details( $order );

                self::send_event(
                    $user_id,
                    '$OrderCancelled',
                    $order->get_billing_email(),
                    $details
                );
            }
        );
    }

    /**
     * Prepare the order details.
     *
     * @param WC_Order $order The order object.
     *
     * @return array
     */
    private static function prepare_order_event_details( $order ) {
        $details = array(
            'unique' => array(
                'key' => $order->get_order_key(),
            ),
            'cart'  => array(
                'items' => self::get_cart_items( $order ),
            ),
        );

        return $details;
    }

    /**
     * Prepare the cart items.
     *
     * @return void
     */
    private static function get_cart_items( $order ) {
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

    /**
     * Return the user ID from the order, if available.
     *
     * @param WC_Order $order
     *
     * @return mixed
     */
    private static function maybe_get_user_id_from_order( $order ) {
        $user_id = null;

        if ( 0 !== $order->get_customer_id() ) {
            $user_id = $order->get_customer_id();
        }

        return $user_id;
    }
}

new WooCommerce_Bento_Events();