# Bento SDK for WordPress & WooCommerce!
[![Build Status](https://travis-ci.org/bentonow/bento-wordpress-sdk.svg?branch=master)](https://travis-ci.org/bentonow/bento-wordpress-sdk)

🍱 Simple, powerful eCommerce analytics for WordPress & WooCommerce projects!

Track events, update data, record LTV and more. Data is stored in your Bento account so you can easily research and investigate what's going on.

👋 To get personalized support, please tweet @bento or email jesse@bentonow.com!

🐶 Tested last on WordPress 6.6.2 and WooCommerce 9.3.3.

> [!IMPORTANT]  
> Please install the Bento plugin on a development or staging site before using it in production. This ensures that all your plugins are compatible and there are no conflicts. Additionally, make sure you have a recent backup handy. Whilst we've tested this plugin on clean installs of WordPress, we can't guarantee there will be no issues due to the nature of the WordPress ecosystem. Use at your own risk (which you can mitigate with testing on a staging site and backing up properly!).

## Requirements

- Bento account and your API keys
- WordPress or WooCommerce site (latest versions recommended)

## Installation

Download and install this package as a plugin, then add your site key. You're done!

## About Caching (Please Read)

For now, Bento's script is personalized and dynamic meaning that it changes on every page load. This is necessary to power our on-page personalization engine and a lot of the magic that's under the hood. Please make sure you exclude your custom Bento.js script if you are using a caching plugin such as WP Rocket or SuperCache.

## Integrations
1. WooCommerce and WooCommerce Subscriptions (event listener - see below)
2. LearnDash (event listener - see below)
3. Easy Digital Downloads (event listener - see below)
4. Elementor Forms (native integration)
5. WPForms (native integration)
6. Bricks Forms (native integration)
7. ThriveLeads (native integration)

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


### LearnDash

#### `$CourseCompleted`

When a user completes a course.

#### `$LessonCompleted`

When a user completes a lesson.

#### `$TopicCompleted`

When a user completes a topic.

#### `$QuizCompleted`

When a user completes a quiz.

#### `$EssayGraded`

When a user's essay has been graded.

#### `$AssignmentApproved`

When a user's assignment has been approved.

#### `$AssignmentNewComment`

When a new comment is added to a user's assignment.

#### `$UserEnrolledInCourse`

When a user enrolls in a course.

#### `$UserEnrolledInGroup`

When a user is enrolled in a group.

#### `$UserPurchasedCourse`

When a user purchases a course.

#### `$UserPurchasedGroup`

When a user purchases a group.

#### `$UserEarnedNewCertificate`

When a user earns a new certificate.

#### `$CourseNotCompleted`

When a user has not completed a course within a specified time frame.

#### `$LessonNotCompleted`

When a user has not completed a lesson within a specified time frame.

#### `$TopicNotCompleted`

When a user has not completed a topic within a specified time frame.

#### `$QuizNotCompleted`

When a user has not completed a quiz within a specified time frame.

#### `$DripContent`

When content becomes available to a user due to drip-feeding.

### Elementor Forms

#### Custom Events

You can choose your own custom event name for any form submission. Just pick "Bento" in "Actions After Submit" and configure it like any other action.

### WPForms

The WPForm integration will send in custom events using the name of the form as the event name.

### Bricks Forms

Guide coming soon.

### ThriveLeads Events

The ThriveLeads integration allows you to create a connection to Bento and will send in custom events for all form submissions. The naming convention for these events is:

`$thrive.optin.{form_identifier}` which will look something like `$thrive.optin.fancy-optin-shortcode-form-v6n9w1`.


## Licence

The Bento Helper is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

The Bento Helper is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
