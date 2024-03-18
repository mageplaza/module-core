/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
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

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/modal'
], function ($, confirmation, alert) {
    "use strict";
    var btnProcess       = $('#mp-btn-progress'),
        isStopBtnClicked = false;

    $.widget('mageplaza.mpProcessBar', {
        options: {
            index: 0,
            itemError: 0,
            itemSuccess: 0,
            confirmMessage: $.mage.__('Do you want to proceed with the process?')
        },

        _create: function () {
            this.initListener();
        },

        initListener: function () {
            var self = this;

            btnProcess.on('click', function (event) {
                event.preventDefault();
                if (self.options.isEnabled === '0') {
                    alert({
                        title: $.mage.__('Warning'),
                        content: $.mage.__('The module has been disabled.')
                    });

                    return;
                }
                self.openConfirmModal();
            });
        },

        openConfirmModal: function () {
            var collection = this.options.collection;

            if (collection.length > 0) {
                this.getConfirmModal();
            } else {
                alert({
                    title: $.mage.__('Warning'),
                    content: $.mage.__('You need to scan all images before starting optimization process.')
                });
            }
        },

        getConfirmModal: function () {
            var self = this;

            confirmation({
                title: $.mage.__('Action'),
                content: this.options.confirmMessage,
                actions: {
                    confirm: function () {
                        var processModal = $('#mp-process-modal');

                        processModal.modal({
                            'type': 'popup',
                            'title': $.mage.__('Processing...'),
                            'responsive': true,
                            'modalClass': 'mp-process-modal-popup',
                            'buttons': [
                                {
                                    text: $.mage.__('Close'),
                                    class: 'mp-action-close',
                                    click: function () {
                                        isStopBtnClicked = true;
                                        var isDone       = false;
                                        confirmation({
                                            content: $.mage.__('Are you sure you want to stop processing?'),
                                            actions: {
                                                confirm: function () {
                                                    location.reload();
                                                },
                                                cancel: function () {
                                                    isStopBtnClicked = false;
                                                    self.loadAjax();
                                                }
                                            }
                                        });

                                    }
                                },
                                {
                                    text: $.mage.__('Reprocess'),
                                    class: 'mp-action-reprocess',
                                    click: function () {
                                        self.processLoading();
                                    }
                                }
                            ]
                        });
                        $('.action-close').hide();
                        processModal.modal('openModal');
                        self.processLoading();
                    }
                }
            });
        },

        processLoading: function () {
            this.options.index     = 0;
            this.options.itemError = 0;
            var parentElement      = document.getElementById("mp-process-modal-content");
            while (parentElement.firstChild){
                parentElement.removeChild(parentElement.firstChild);
            }

            this.loadAjax();
        },

        loadAjax: function () {
            if (isStopBtnClicked) {
                return;
            }

            var self              = this,
                collection        = this.options.collection,
                contentProcessing = $('.mp-process-modal-content-processing'),
                progressBar       = $('#mp-progress-bar'),
                item              = collection[this.options.index],
                collectionLength  = collection.length,
                percent           = 100 * (this.options.index + 1) / collectionLength,
                btnClose          = $('button.mp-action-close'),
                btnReprocess      = $('button.mp-action-reprocess'),
                popupTitle        = $('.mp-process-modal-popup .modal-title');

            btnReprocess.hide();
            if (this.options.index >= collectionLength && this.options.itemError !== collectionLength) {
                popupTitle.text($.mage.__('Complete'));
                contentProcessing.text($.mage.__(''));
                progressBar.removeClass('progress-bar-info');
                progressBar.addClass('progress-bar-success');

                return;
            }

            if (this.options.itemError === collectionLength) {
                popupTitle.text($.mage.__('Process failed'));
                contentProcessing.text($.mage.__(''));
                btnClose.hide();
                btnReprocess.show();

                return;
            }

            contentProcessing.text(
                $.mage.__('Processing: %1 / %2')
                .replace('%1', this.options.itemSuccess + 1)
                .replace('%2', collectionLength));

            return $.ajax({
                url: this.options.url,
                data: {
                    item_id: item.id,
                    form_key: window.FORM_KEY
                }
            }).done(function (data) {
                var progressBar  = $('#mp-progress-bar'),
                    modalPercent = $('#mp-process-modal-percent');

                if (data.status === 'Error') {
                    self.options.itemError++;
                    if (percent === 100 && self.options.itemError === collectionLength) {
                        progressBar.width('100%');
                        modalPercent.text('0/' + collectionLength + ' (0.00%)');
                        progressBar.removeClass('progress-bar-info');
                        progressBar.addClass('progress-bar-danger');

                    }
                    self.getContent(percent, data.item_error, data.status);
                } else {
                    self.options.itemSuccess++;
                }
                self.options.index++;
                progressBar.width(percent.toFixed(2) + '%');
                modalPercent.text(self.options.itemSuccess + '/' + collectionLength + ' (' + percent.toFixed(2) + '%)');

                self.loadAjax();
            }).fail(function (data) {
                self.getContent(percent, data.item_error, data.status);
                self.loadAjax();
            });
        },

        getContent: function (percent, itemError, status) {
            var modalContent = $('#mp-process-modal-content');

            modalContent.append('<p>' + '<strong>' + status + '</strong>' + ': ' + itemError + '</p>');
        }
    });

    return $.mageplaza.mpProcessBar;
});
