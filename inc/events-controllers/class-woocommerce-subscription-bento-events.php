<?php
/**
 * WooCommerce Subscription - Bento Events Controller
 *
 * @package BentoHelper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WooCommerce_Subscription_Bento_Events extends WooCommerce_Bento_Events {

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct() {
        add_action(
            'woocommerce_checkout_subscription_created',
            function( $subscription ) {
                $user_id = self::maybe_get_user_id_from_order( $subscription );
                $details = self::prepare_subscription_event_details( $subscription );

                self::send_event(
                    $user_id,
                    '$SubscriptionCreated',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_subscription_status_active',
            function ( $subscription ) {
                $user_id = self::maybe_get_user_id_from_order( $subscription );
                $details = self::prepare_subscription_event_details( $subscription );

                self::send_event(
                    $user_id,
                    '$SubscriptionActive',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_subscription_status_cancelled',
            function ( $subscription ) {
                $user_id = self::maybe_get_user_id_from_order( $subscription );
                $details = self::prepare_subscription_event_details( $subscription );

                self::send_event(
                    $user_id,
                    '$SubscriptionCancelled',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_subscription_status_expired',
            function ( $subscription ) {
                $user_id = self::maybe_get_user_id_from_order( $subscription );
                $details = self::prepare_subscription_event_details( $subscription );

                self::send_event(
                    $user_id,
                    '$SubscriptionExpired',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_subscription_status_on-hold',
            function ( $subscription ) {
                $user_id = self::maybe_get_user_id_from_order( $subscription );
                $details = self::prepare_subscription_event_details( $subscription );

                self::send_event(
                    $user_id,
                    '$SubscriptionOnHold',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_scheduled_subscription_trial_end',
            function( $subscription_id ) {
                $subscription = wcs_get_subscription( $subscription_id );
                $user_id      = self::maybe_get_user_id_from_order( $subscription );
                $details      = self::prepare_subscription_event_details( $subscription );

                self::send_event(
                    $user_id,
                    '$SubscriptionTrialEnded',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );

        add_action(
            'woocommerce_scheduled_subscription_payment',
            function( $subscription_id ) {
                $subscription = wcs_get_subscription( $subscription_id );
                $user_id      = self::maybe_get_user_id_from_order( $subscription );
                $details      = self::prepare_subscription_event_details( $subscription );

                $order = $subscription->get_last_order( 'all' );

                if ( $order->get_total() > 0 ) {
                    $details['value'] = array(
                        'currency' => $subscription->get_currency(),
                        'amount'   => $subscription->get_total(),
                    );
                }

                self::send_event(
                    $user_id,
                    '$SubscriptionRenewed',
                    $subscription->get_billing_email(),
                    $details
                );
            }
        );
    }

    /**
     * Prepare the subscription details.
     *
     * @param WC_Subscription $subscription The subscription object.
     *
     * @return array
     */
    private static function prepare_subscription_event_details( $subscription ) {
        $order = $subscription->get_last_order( 'all' );

        $details = array(
            'unique' => array(
                'key' => $order->get_order_key(),
            ),
            'subscription' => array(
                'id'     => $subscription->get_id(),
                'status' => $subscription->get_status(),
                'order'  => array(
                    'items' => self::get_cart_items( $subscription ),
                ),
            ),
        );

        return $details;
    }
}

new WooCommerce_Subscription_Bento_Events();