/* Helpers */
const debounce = (func, delay) => {
  let inDebounce;
  return function () {
    const context = this;
    const args = arguments;
    clearTimeout(inDebounce);
    inDebounce = setTimeout(() => func.apply(context, args), delay);
  };
};

/* Get cart hash key */
const getCartHashKey = function () {
  if (wc_cart_fragments_params) {
    const cart_hash_key = wc_cart_fragments_params.cart_hash_key;

    try {
      const localStorageItem = localStorage.getItem(cart_hash_key);
      const sessionStorageItem = sessionStorage.getItem(cart_hash_key);

      if (localStorageItem) {
        return localStorageItem;
      }

      if (sessionStorageItem) {
        return sessionStorageItem;
      }

      return null;
    } catch (e) {}
  }
};

/* identifyUser */
const isValidEmail = function (email) {
  if (typeof email === 'string') {
    return /[^\s@]+@[^\s@]+\.[^\s@]+/.test(email);
  }

  return false;
};

const identifyUser = function () {
  const billing_email = bento$('#billing_email').val();
  const email = isValidEmail(billing_email) ? billing_email : null;

  if (typeof email === 'string') {
    bento.identify(email);
  } else {
    if (bento_wordpress_sdk_params.user_logged_in) {
      bento.identify(bento_wordpress_sdk_params.user_email);
    }
  }
};

/* On email input change */
const onEmailInputChange = debounce(function () {
  identifyUser();
}, 500);

/* $woocommerceCartCreated */
const cartCreatedEvent = debounce(function () {
  console.log('$woocommerceCartCreated');

  identifyUser();

  var data = {
    action: 'bento_get_cart_items',
  };
  bento$.get(bento_wordpress_sdk_params.ajax_url, data, function (response) {
    bento.track('$woocommerceCartCreated', JSON.parse(response));
  });
});

(function ($) {
  if (typeof bento$ != 'undefined') {
    bento$(function () {
      let cartIsEmpty =
        getCartHashKey() === null &&
        bento_wordpress_sdk_params.woocommerce_cart_count === '0';

      console.log(cartIsEmpty);
      console.log(bento_wordpress_sdk_params);

      if (bento_wordpress_sdk_params.woocommerce_enabled) {
        console.log('WooCommerce Enabled');

        bento$(document.body).on('added_to_cart', (e) => {
          if (cartIsEmpty) {
            cartIsEmpty = false;
            cartCreatedEvent();
          }
        });

        /**
         * Watch for email input changes.
         */
        bento$('#billing_email').bind('blur', onEmailInputChange);

        // /**
        //  * Listen for cart change events then send cart data.
        //  */
        // bento$(document.body).on(
        //   'added_to_cart removed_from_cart updated_cart_totals updated_shipping_method applied_coupon removed_coupon updated_checkout',
        //   function (event) {
        //     if (wc_cart_fragments_params) {
        //       // Get cart hash key from wc_cart_fragments_params variable
        //       var cart_hash_key = wc_cart_fragments_params.cart_hash_key;

        //       try {
        //         // Get local storage and session storage for cart dash
        //         var localStorageItem = localStorage.getItem(cart_hash_key);
        //         var sessionStorageItem = sessionStorage.getItem(cart_hash_key);

        //         // Check if have local storage or session storage
        //         if (localStorageItem || sessionStorageItem) {
        //           // Have items so we'll send the cart data now
        //           console.log(localStorageItem, sessionStorageItem);
        //         }
        //       } catch (e) {}
        //     }
        //     sendCartData();
        //   }
        // );

        // /**
        //  * Watch for fragments separate and determine if have data to send.
        //  * As this event may trigger with no items in cart (initial page
        //  * load) but no need to send cart then. So we check for items.
        //  */
        $(document.body).on(
          'wc_fragments_refreshed wc_fragment_refresh',
          function (event) {
            console.log(wc_cart_fragments_params);
          }
        );
      }
      bento.view();
    });
  }
})(jQuery);
