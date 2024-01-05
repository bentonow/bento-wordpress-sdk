# Bento SDK for WordPress & WooCommerce!
[![Build Status](https://travis-ci.org/bentonow/bento-wordpress-sdk.svg?branch=master)](https://travis-ci.org/bentonow/bento-wordpress-sdk)

🍱 Simple, powerful eCommerce analytics for WordPress & WooCommerce projects!

Track events, update data, record LTV and more. Data is stored in your Bento account so you can easily research and investigate what's going on.

👋 To get personalized support, please tweet @bento or email jesse@bentonow.com!

🐶 Battle-tested on Bento Production!

## Installation

Download and install this package as a plugin, then add your site key. You're done!

## About Caching (Please Read)

For now, Bento's script is personalized and dynamic meaning that it changes on every page load. This is necessary to power our on-page personalization engine and a lot of the magic that's under the hood. Please make sure you exclude your custom Bento.js script if you are using a caching plugin such as WP Rocket or SuperCache.

## Events

### WooCommerce

#### `$OrderPlaced`

When an order is placed in WooCommerce. A persons lifetime value (LTV) will be increased in Bento for the order total.

#### `$OrderRefunded`

If an order is refunded, whether partial or full, it will deduct the LTV of a person in based the refunded amount.

#### `$OrderCancelled`

When an order status is changed to `cancelled`.

#### `$OrderShipped`

When an order status has been changed to `completed`.


### WooCommerce Subscriptions

#### `$SubscriptionCreated`

When a new subscription is created, regardless of its status.

#### `$SubscriptionActive`

When a new subscription becomes active, such as after a trial has ended.

#### `$SubscriptionCancelled`

When a subscription is cancelled by an admin. Note this will not be triggered if a customer cancels their subscription, only when a subscription comes to the end of a prepaid term will it be cancelled.

#### `$SubscriptionExpired`

When a subscription reaches the end of it's term.

#### `$SubscriptionOnHold`

When the status of a subscription changes to `on-hold`

#### `$SubscriptionTrialEnded`

When the trial period of a subscription has reached its end date.

#### `$SubscriptionRenewed`

When a subscription renewal payment is processed.

### Easy Digital Downloads

#### `$DownloadPurchased`

When a payment is complete for a download.

#### `$DownloadDownloaded`

When a download is downloaded by a user.

#### `$DownloadRefunded`

When a download is refunded, either partially or full.

## Licence

The Bento Helper is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

The Bento Helper is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
