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
  if (typeof wc_cart_fragments_params !== "undefined") {
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

const onEmailInputChange = debounce(function () {
  identifyUser();
}, 500);

/* Bento events */

const sendBentoEventWithCart = debounce(function (eventName) {
  identifyUser();

  const data = {
    action: 'bento_get_cart_items',
  };

  bento$.get(bento_wordpress_sdk_params.ajax_url, data, function (response) {
    bento.track(eventName, { cart: { items: JSON.parse(response) } });
  });
}, 500);

(function ($) {
  if (typeof bento$ != 'undefined') {
    bento$(function () {
      let cartIsEmpty = isCartEmpty();

      if (bento_wordpress_sdk_params.woocommerce_enabled) {
        bento$(document.body).on('added_to_cart', () => {
          if (cartIsEmpty) {
            cartIsEmpty = false;
            sendBentoEventWithCart('$CartCreated');
          } else {
            sendBentoEventWithCart('$CartUpdated');
          }
        });

        bento$(document.body).on(
          'updated_cart_totals removed_from_cart',
          () => {
            sendBentoEventWithCart('$CartUpdated');
          }
        );

        if ($('form.woocommerce-checkout').length > 0) {
          sendBentoEventWithCart('$StartedCheckout');
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
