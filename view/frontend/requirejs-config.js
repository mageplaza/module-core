/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    map: {
        '*': {
            'mageplaza/core/jquery/popup': 'Mageplaza_Core/js/jquery.magnific-popup.min',
            'mageplaza/core/owl.carousel': 'Mageplaza_Core/js/owl.carousel.min',
            'mageplaza/core/bootstrap': 'Mageplaza_Core/js/bootstrap.min'
        }
    },
    shim: {
        "mageplaza/core/jquery/popup": ["jquery"],
        "mageplaza/core/owl.carousel": ["jquery"],
        "mageplaza/core/bootstrap": ["jquery"]
    }
};
