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
     * The events that should include value in details.
     *
     * @var array
     */
    protected static $events_with_value = array(
        '$OrderPlaced',
    );

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

                $user_id = self::maybe_get_user_id_from_order( $order );

                self::send_event(
                    $user_id,
                    '$OrderPlaced',
                    $order->get_billing_email(),
                    self::get_order_details( $order, '$OrderPlaced' )
                );
            }
        );
    }

    /**
     * Prepare the order details.
     *
     * @param WC_Order $order The order object.
     * @param string   $type  The event type.
     *
     * @return array
     */
    private static function get_order_details( $order_id, $type ) {
        $order = wc_get_order( $order_id );

        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $details = array(
            'unique' => array(
                'key' => $order->get_order_key(),
            ),
            'cart'  => array(
                'items' => self::get_cart_items( $order ),
            ),
        );

        if ( in_array( $type, self::$events_with_value ) ) {
            $value = self::get_event_value( $type, $order );

            if ( ! empty( $value ) ) {
                $details['value'] = $value;
            }
        }

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
     * Get the event value based on the event type.
     *
     * @param string   $type  The event type.
     * @param WC_Order $order The order object.
     *
     * @return array
     */
    private static function get_event_value( $type, $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $value = array(
            'currency' => $order->get_currency(),
        );

        if ( '$OrderPlaced' === $type ) {
            $value['amount'] = $order->get_total();
        }

        return $value;
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