<?php
/**
 * SureCart - Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'SureCart' ) && ! class_exists( 'SureCart_Bento_Events', false ) ) {
    /**
     * SureCart Bento Events
     */
    class SureCart_Bento_Events extends Bento_Events_Controller {

        /**
         * Constructor.
         *
         * @return void
         */
        public function __construct() {

            // Handles checkout confirmation
            add_action(
                'surecart/checkout_confirmed',
                array( $this, 'handle_checkout_confirmed' ),
                10,
                2
            );
        }

        /**
         * Handle checkout confirmed event.
         *
         * @param object $checkout The checkout object.
         * @param object $request The request object.
         */
        public function handle_checkout_confirmed( $checkout, $request ) {
            $user_id = $checkout->customer ? $checkout->customer->id : null;

            $details = $this->prepare_checkout_event_details( $checkout );

            $custom_fields = [];
            if (is_array($checkout->metadata) || is_object($checkout->metadata)) {
                $metadata = (array) $checkout->metadata;
                foreach ($metadata as $key => $value) {
                    $custom_fields[$key] = $value;
                }
            }
            // Add additional fields from checkout
            $additional_fields = [
                'first_name' => $checkout->first_name,
                'name' => $checkout->name,
                'phone' => $checkout->phone,
                'last_name' => $checkout->last_name,
            ];

            // Merge additional fields with custom fields
            $custom_fields = array_merge($custom_fields, $additional_fields);

            self::send_event(
                $user_id,
                '$CheckoutConfirmed',
                $checkout->email,
                $details,
                $custom_fields,
            );
        }

        /**
         * Prepare the order details.
         *
         * @param \SureCart\Models\Order $order The order object.
         *
         * @return array
         */
        private function prepare_order_event_details( $order ) {
            $details = array(
                'unique' => array(
                    'key' => $order->id,
                ),
                'cart'  => array(
                    'items' => $this->get_cart_items( $order ),
                ),
            );

            return $details;
        }

        /**
         * Prepare the checkout details.
         *
         * @param object $checkout The checkout object.
         *
         * @return array
         */
        private function prepare_checkout_event_details( $checkout ) {
            $details = array(
                'unique' => array(
                    'key' => $checkout->id,
                ),
                'amount' => $checkout->total_amount,
                'currency' => $checkout->currency,
                'status' => $checkout->status,
            );

            return $details;
        }

        /**
         * Prepare the cart items.
         *
         * @param \SureCart\Models\Order $order The order object.
         *
         * @return array
         */
        protected function get_cart_items( $order ) {
            $items = array();

            $order = \SureCart\Models\Order::with([
                'line_items',
                'line_item.price',
                'price.product'
            ])->find($order->id);

            foreach ( $order->line_items as $item ) {
                $items[] = array(
                    'product_id'    => $item->price->product->id,
                    'product_name'  => $item->price->product->name,
                    'product_sku'   => $item->price->product->sku,
                    'quantity'      => $item->quantity,
                    'price'         => $item->price->amount,
                    'currency'      => $item->price->currency,
                    'total_amount'  => $item->total,
                );
            }

            return $items;
        }
    }

    new SureCart_Bento_Events();
}