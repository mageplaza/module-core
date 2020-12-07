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

require([
    'jquery',
    'underscore'
], function ($, _) {
    'use strict';

    var mpHelpDb = {
        'admin/system_config/index': [
            {
                'css_selector': '#general_single_store_mode_enabled',
                'type': 'link',
                'text': 'How to enable Single Store Mode, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-enable-single-store-mode-magento-2.html',
                'anchor': 'learn more'
            }
        ],
        'theme/design_config/edit/scope/websites/scope_id': [
            {
                'css_selector': 'input[name*="header_welcome"]',
                'type': 'link',
                'text': 'How to change the welcome message, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-change-welcome-message-magento-2.html',
                'anchor': 'learn more'
            }
        ],
        'system_config/edit/section/contact': [
            {
                'css_selector': '#contact_contact_enabled',
                'type': 'link',
                'text': 'How to configure Contact Us form, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-configure-contacts-email-address-magento-2.html',
                'anchor': 'learn more'
            }
        ],
        //Not Configuration
        'catalog/product/edit': [
            {
                'css_selector': '#media_gallery_content',
                'type': 'link',
                'text': 'How to upload Images Product, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-to-upload-images-product-in-magento-2.html',
                'anchor': 'learn more'
            }
        ],
        'type/configurable/key/': [
            {
                'css_selector': '.page-actions-placeholder',
                'type': 'link',
                'text': 'Create Configurable Product, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-create-configurable-product-magento-2.html',
                'anchor': 'learn more'
            }
        ],
        'system_config/edit/section/general': [
            {
                'css_selector': '#general_region_state_required',
                'type': 'link',
                'text': 'How to setup State, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-setup-locale-state-country-magento-2.html#set-up-state',
                'anchor': 'learn more'
            },
            {
                'css_selector': '#general_country_default',
                'type': 'link',
                'text': 'How to setup Country, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-setup-locale-state-country-magento-2.html#set-up-country',
                'anchor': 'learn more'
            },
            {
                'css_selector': '#general_locale_timezone',
                'type': 'link',
                'text': 'How to setup Locale, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-setup-locale-state-country-magento-2.html#login-magento-2',
                'anchor': 'learn more'
            },
            {
                'css_selector': '#general_store_information_name',
                'type': 'link',
                'text': 'How to setup store information, {link}.',
                'url': 'https://www.mageplaza.com/kb/how-setup-store-information-magento-2.html',
                'anchor': 'learn more'
            }

        ],
        'system_config/edit/section/trans_email': [
            {
                'css_selector': '#trans_email_ident_sales_email',
                'type': 'link',
                'text': 'About 79% of visitors drop their shopping cart at the checkout page. This proven abandoned cart email templates that can improve that number, {link}.',
                'url': 'https://pages.mageplaza.com/abandoned-cart-email-templates-for-magento/',
                'anchor': 'learn more'
            },
        ],
        'system_config/edit/section/newsletter': [
            {
                'css_selector': '#newsletter_subscription_success_email_template',
                'type': 'link',
                'text': 'Welcome emails generate 4 times the total open rates and 5 times the click rates compared to other bulk promotions. Get proven welcome email templates, {link}.',
                'url': 'https://pages.mageplaza.com/welcome-email-templates-for-magento-2/',
                'anchor': 'get a copy'
            }


        ],
        'admin/email_template/new': [
            {
                'css_selector': '#template_select',
                'type': 'link',
                'text': 'Get {link} templates that convert',
                'url': 'https://pages.mageplaza.com/bundle-of-email-follow-up-templates/',
                'anchor': 'bundle of follow up emails'
            }


        ]
    };

    function buildHtml(data) {
        var link = '<a href="' + data.url + '?utm_source=store&utm_medium=link&utm_campaign=mageplaza-helps" target="_blank">' + data.anchor + '</a>';
        var text = data.text.replace('{link}', link);

        return '<p class="note">' + text + '</p>';
    }

    var url = window.location.href;
    for (var path in mpHelpDb) {
        if (mpHelpDb.hasOwnProperty(path) && url.search(path)) {
            var datas = mpHelpDb[path];
            _.each(datas, function (data) {
                var html = buildHtml(data);
                html && $(html).insertAfter(data.css_selector); //only insert if html is not empty
            });
        }
    }
});