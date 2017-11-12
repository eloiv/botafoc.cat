(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.detailClosed = {
    attach: function (context, settings) {
      $('.install-page details summary',context).click(function() {
        $('details',context).removeAttr('open');
      });

      $(".layout-region-node-footer #edit-footer input").change(function() {
          $(".layout-region-node-secondary-top #edit-footer input").prop("checked", this.checked);
      });

      $(".layout-region-node-secondary-top #edit-footer input").change(function() {
          $(".layout-region-node-footer #edit-footer input").prop("checked", this.checked);
      });
    }
  };

})(jQuery, Drupal);
