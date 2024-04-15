/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Core
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

var config = {
    paths: {
        'jquery/file-uploader': 'Mageplaza_Core/lib/fileUploader/jquery.fileuploader',
        'mageplaza/core/jquery/popup': 'Mageplaza_Core/js/jquery.magnific-popup.min',
        'mageplaza/core/owl.carousel': 'Mageplaza_Core/js/owl.carousel.min',
        'mageplaza/core/bootstrap': 'Mageplaza_Core/js/bootstrap.min',
        mpIonRangeSlider: 'Mageplaza_Core/js/ion.rangeSlider.min',
        touchPunch: 'Mageplaza_Core/js/jquery.ui.touch-punch.min',
        mpDevbridgeAutocomplete: 'Mageplaza_Core/js/jquery.autocomplete.min'
    },
    shim: {
        "mageplaza/core/jquery/popup": ["jquery"],
        "mageplaza/core/owl.carousel": ["jquery"],
        "mageplaza/core/bootstrap": ["jquery"],
        mpIonRangeSlider: ["jquery"],
        mpDevbridgeAutocomplete: ["jquery"],
        touchPunch: ['jquery', 'jquery-ui-modules/core', 'jquery-ui-modules/mouse', 'jquery-ui-modules/widget']
    }
};
