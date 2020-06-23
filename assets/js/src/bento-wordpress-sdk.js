(function ($) {
  if (typeof bento$ != 'undefined') {
    bento$(function () {
      bento.view();
      console.log(bento_wordpress_sdk_params);
      if (bento_wordpress_sdk_params.woocommerce_enabled) {
        console.log('test');
      }
    });
  }
})(jQuery);
