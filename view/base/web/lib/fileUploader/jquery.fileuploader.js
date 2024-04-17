/**
 * Custom Uploader
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global define, require */

(function (factory) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    // Register as an anonymous AMD module:
    define([
      'jquery',
      'Mageplaza_Core/lib/fileUploader/jquery.fileupload-image',
      'Mageplaza_Core/lib/fileUploader/jquery.fileupload-audio',
      'Mageplaza_Core/lib/fileUploader/jquery.fileupload-video',
      'Mageplaza_Core/lib/fileUploader/jquery.iframe-transport',
    ], factory);
  } else if (typeof exports === 'object') {
    // Node/CommonJS:
    factory(
      require('jquery'),
      require('Mageplaza_Core/lib/fileUploader/jquery.fileupload-image'),
      require('Mageplaza_Core/lib/fileUploader/jquery.fileupload-audio'),
      require('Mageplaza_Core/lib/fileUploader/jquery.fileupload-video'),
      require('Mageplaza_Core/lib/fileUploader/jquery.iframe-transport')
    );
  } else {
    // Browser globals:
    factory(window.jQuery);
  }
})();
