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
            self.options.index = 0;
            confirmation({
                title: $.mage.__('Action'),
                content: this.options.confirmMessage,
                actions: {
                    confirm: function () {
                        var processModal = $('#mp-process-modal');
                        isStopBtnClicked = false;
                        processModal.modal({
                            'type': 'popup',
                            'title': $.mage.__('Processing...'),
                            'responsive': true,
                            'modalClass': 'mp-process-modal-popup',
                            'buttons': [
                                {
                                    text: $.mage.__('Stop'),
                                    class: 'mp-action-stop action-primary',
                                    click: function () {
                                        isStopBtnClicked = true;
                                        confirmation({
                                            content: $.mage.__('Are you sure you want to stop processing?'),
                                            actions: {
                                                confirm: function () {
                                                    processModal.modal('closeModal');
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
                                    text: $.mage.__('Close'),
                                    class: 'mp-action-close action-secondary',
                                    click: function () {
                                        processModal.modal('closeModal');
                                    }
                                },
                                {
                                    text: $.mage.__('Reprocess'),
                                    class: 'mp-action-reprocess action-primary',
                                    click: function () {
                                        self.processLoading();
                                    }
                                }
                            ]
                        });

                        $('.action-close').hide();
                        $('#mp-process-modal-content').remove();
                        $('#mp-process-modal-content-line').remove();
                        $('.modal-footer').after('<div id="mp-process-modal-content" style="padding: 2rem 3rem;"></div>')
                        .after('<div style="padding:0 3rem"><div id="mp-process-modal-content-line" style="height: 1px; background:#DCDCDC;"></div></div>');
                        $('.modal-slide .modal-content').css('padding-bottom', 'unset');
                        $('.modal-popup .modal-footer').css('padding-top', 'unset');
                        processModal.modal('openModal');
                        self.processLoading();
                    }
                }
            });
        },

        processLoading: function () {
            this.options.index     = 0;
            this.options.itemError = 0;
            var parentElement      = document.querySelectorAll("#mp-error-item"),
                progressBar        = $('#mp-progress-bar');
            parentElement.forEach(function (element) {
                element.parentNode.removeChild(element);
            });
            $('.mp-process-modal-popup .modal-title').text($.mage.__('Processing...'));
            progressBar.width('0%');
            $('#mp-process-modal-percent').text('0/0 (0.00%)');
            progressBar.removeClass('progress-bar-success');
            progressBar.removeClass('progress-bar-danger');
            progressBar.removeClass('progress-bar-info');
            progressBar.addClass('progress-bar-info');
            this.loadAjax();
        },

        loadAjax: function () {
            if (isStopBtnClicked) {
                return;
            }

            var self             = this,
                collection       = this.options.collection,
                progressBar      = $('#mp-progress-bar'),
                modalPercent     = $('#mp-process-modal-percent'),
                item             = collection[this.options.index],
                collectionLength = collection.length,
                percent          = 100 * (this.options.index + 1) / collectionLength,
                btnClose         = $('button.mp-action-close'),
                btnStop          = $('button.mp-action-stop'),
                btnReprocess     = $('button.mp-action-reprocess'),
                popupTitle       = $('.mp-process-modal-popup .modal-title');

            btnStop.show();
            btnReprocess.hide();
            btnClose.hide();
            if (this.options.itemError === collectionLength) {
                popupTitle.text($.mage.__('Process failed'));
                btnClose.css({
                    'position': 'relative',
                    'left': '-5px'
                });
                btnStop.hide();
                btnClose.show();
                btnReprocess.show();

                return;
            }

            if (this.options.index >= collectionLength) {
                if (this.options.itemError !== 0) {
                    popupTitle.text($.mage.__('Complete'));
                    progressBar.removeClass('progress-bar-info');
                    progressBar.addClass('progress-bar-success');
                    btnStop.hide();
                    btnReprocess.show();
                    btnClose.show();

                    return;
                } else {
                    popupTitle.text($.mage.__('Complete'));
                    progressBar.removeClass('progress-bar-info');
                    progressBar.addClass('progress-bar-success');
                    btnStop.hide();
                    btnClose.show();

                    return;
                }
            }

            self.options.index++;

            return $.ajax({
                url: this.options.url,
                data: {
                    item_id: item.id,
                    item_name: item.name,
                    form_key: window.FORM_KEY
                }
            }).done(function (data) {
                if (data.status === 'Error') {
                    self.options.itemError++;
                    if (percent === 100 && self.options.itemError === collectionLength) {
                        progressBar.removeClass('progress-bar-info');
                        progressBar.addClass('progress-bar-danger');

                    }
                    self.getContent(percent, data.item_error, data.status);
                }

                progressBar.width(percent.toFixed(2) + '%');
                modalPercent.text(self.options.index + '/' + collectionLength + ' (' + percent.toFixed(2) + '%)');

                self.loadAjax();
            }).fail(function (data) {
                self.getContent(percent, data.item_error, data.status);
                self.loadAjax();
            });
        },

        getContent: function (percent, itemError, status) {
            var modalContent = $('#mp-process-modal-content');

            modalContent.append('<p id="mp-error-item" style="text-align: left">' + '<strong style="color: red">' + status + '</strong>' + ': ' + itemError + '</p>');
        }
    });

    return $.mageplaza.mpProcessBar;
});
