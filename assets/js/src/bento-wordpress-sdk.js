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

const isCartEmpty = function () {
  return (
    getCartHashKey() === null &&
    bento_wordpress_sdk_params.woocommerce_cart_count === '0'
  );
};

const sendBentoEventWithCart = function (eventName) {
  const data = {
    action: 'bento_get_cart_items',
  };

  bento$.get(bento_wordpress_sdk_params.ajax_url, data, function (response) {
    bento.track(eventName, JSON.parse(response));
  });
};

/* Identify current user */

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

  sendBentoEventWithCart('$woocommerceCartCreated');
}, 500);

/* $woocommerceCartUpdated */
const cartUpdatedEvent = debounce(function () {
  console.log('$woocommerceCartUpdated');

  identifyUser();

  sendBentoEventWithCart('$woocommerceCartUpdated');
}, 500);

/* $woocommerceStartedCheckout */
const startedCheckoutEvent = debounce(function () {
  console.log('$woocommerceStartedCheckout');

  identifyUser();

  sendBentoEventWithCart('$woocommerceStartedCheckout');
}, 500);

(function ($) {
  if (typeof bento$ != 'undefined') {
    bento$(function () {
      let cartIsEmpty = isCartEmpty();

      if (bento_wordpress_sdk_params.woocommerce_enabled) {
        bento$(document.body).on('added_to_cart', () => {
          if (cartIsEmpty) {
            cartIsEmpty = false;
            cartCreatedEvent();
          }
        });

        bento$(document.body).on(
          'updated_cart_totals added_to_cart removed_from_cart',
          () => {
            cartUpdatedEvent();
          }
        );

        if ($('.woocommerce-checkout').length > 0) {
          startedCheckoutEvent();
        }

        /**
         * Watch for email input changes.
         */
        bento$('#billing_email').bind('blur', onEmailInputChange);
      }
      bento.view();
    });
  }
})(jQuery);
