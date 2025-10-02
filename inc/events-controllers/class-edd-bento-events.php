<?php
/**
 * Easy Digital Downloads - Bento Events Controller
 *
 * @package BentoHelper
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'Easy_Digital_Downloads' ) && ! class_exists( 'EDD_Bento_Events', false ) ) {
    /**
     * Easy Digital Downloads Events
     */
    class EDD_Bento_Events extends Bento_Events_Controller {

        /**
         * Constructor.
         *
         * @return void
         */
        public function __construct() {
            add_action(
                'edd_complete_purchase',
                function( $payment_id ) {
                    $order    = edd_get_order( $payment_id );

                    if ( ! $order || empty( $order->email ) || ! is_email( $order->email ) ) {
                        return;
                    }

                    $details  = self::prepare_download_event_details( $order );

                    if ( $order->total > 0 ) {
                        $details['value'] = array(
                            'currency' => $order->currency,
                            'amount'   => $order->total
                        );
                    }

                    self::send_event(
                        self::maybe_get_user_id_from_order( $order ),
                        '$DownloadPurchased',
                        $order->email,
                        $details
                    );
                },
                10,
                3
            );

            add_action(
                'edd_process_verified_download',
                function( $download_id, $email ) {
                    $download = edd_get_download( $download_id );

                     if ( ! $download || empty( $email ) || ! is_email( $email ) ) {
                         return;
                     }

                    $details = array(
                        'download' => array(
                            'id'        => $download_id,
                            'name'      => $download->get_name(),
                            'permalink' => get_permalink( $download_id ),
                            'price'     => $download->get_price(),
                            'sku'       => $download->get_sku(),
                        ),
                    );

                    self::send_event(
                        email_exists( $email ),
                        '$DownloadDownloaded',
                        $email,
                        $details
                    );
                },
                10,
                2
            );

            add_action(
                'edd_refund_order',
                function( $order_id, $refund_id, $all_refunded ) {
                    $refund  = edd_get_order( $refund_id );

                    if ( ! $refund || empty( $refund->email ) || ! is_email( $refund->email ) ) {
                        return;
                    }

                    $details = self::prepare_download_event_details( $refund );

                    if ( $refund->total < 0 ) {
                        $details['value'] = array(
                            'currency' => $refund->currency,
                            'amount'   => $refund->total
                        );
                    }

                    self::send_event(
                        self::maybe_get_user_id_from_order( $refund ),
                        '$DownloadRefunded',
                        $refund->email,
                        $details
                    );
                },
                10,
                3
            );
        }

        /**
         * Prepare the download details.
         *
         * @param Order  $order The order object.
         * @param string $key   Unique order key.
         *
         * @return mixed
         */
        protected static function prepare_download_event_details( $order, $key = null ) {
            if ( ! $order ) {
                return null;
            }

            $details = array(
                'unique' => array(
                    'key' => $key ? $key : $order->get_number(),
                ),
                'cart' => array(
                    'items' => self::get_cart_items( $order ),
                ),
            );

            return $details;
        }

        /**
         * Prepare the cart items from the order.
         *
         * @param Order $order The order object.
         *
         * @return mixed
         */
        protected static function get_cart_items( $order ) {
            $base_currency = edd_get_currency();

            $items = array();

            foreach( $order->get_items() as $item ) {
                $download = edd_get_download( $item->product_id );

                if ( ! $download ) {
                    continue;
                }

                $items[] = array(
                    'shop_base_currency'    => $base_currency,
                    'product_id'            => $item->product_id,
                    'product_name'          => $item->product_name,
                    'product_permalink'     => get_permalink( $item->product_id ),
                    'product_price'         => $item->amount,
                    'product_sku'           => $download->get_sku(),
                    'quantity'              => $item->quantity,
                );
            }

            return $items;
        }

        /**
         * Return the user ID from the order, if available.
         *
         * @param Order $order The order object.
         *
         * @return mixed
         */
        protected static function maybe_get_user_id_from_order( $order ) {
            $user_id = null;

            if (
                $order &&
                is_a( $order, 'EDD\Orders\Order' ) &&
                $order->user_id
            ) {
                $user_id = $order->user_id;
            }

            return $user_id;
        }
    }

    new EDD_Bento_Events();
}
