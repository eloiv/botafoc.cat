(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.detailClosed = {
    attach: function (context, settings) {
      $('.install-page details summary',context).click(function() {
        $('details',context).removeAttr('open');
      });
    }
  };

})(jQuery, Drupal);
