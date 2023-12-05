<?php
/**
 * Easy Digital Downloads - Bento Events Controller
 *
 * @package BentoHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EDD_Bento_Events extends Bento_Events_Controller {

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        add_action(
            'edd_complete_download_purchase',
            function( $download_id, $order_id, $download_type ) {
                $download = edd_get_download( $download_id );
                $order    = edd_get_order( $order_id );

                $details = array(
                    'download' => self::prepare_download_event_details( $download ),
                    'value'    => array(
                        'currency' => $order->currency,
                        'amount'   => $download->get_price()
                    ),
                );

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
    }

    /**
     * Prepare the download details.
     *
     * @param EDD_Download $download The download object.
     *
     * @return mixed
     */
    protected static function prepare_download_event_details( $download ) {
        if ( ! $download ) {
            return null;
        }

        $details = array(
            'type'  => $download_type,
            'id'    => $download->get_id(),
            'name'  => $download->get_name(),
            'price' => $download->get_price(),
            'notes' => $download->get_notes(),
            'url'   => get_permalink( $download->get_id() ),
        );

        return $details;
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