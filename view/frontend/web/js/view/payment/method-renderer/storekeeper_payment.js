/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/url',
        'Magento_Checkout/js/action/place-order'
    ],
    function (ko, $, Component, url, placeOrderAction) {
        'use strict';
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'StoreKeeper_StoreKeeper/payment/storekeeper_payment'
            },
            getData: function () {
                var dob_format = '';
                if (this.dateofbirth != null) {
                    var dob = new Date(this.dateofbirth);
                    var dd = dob.getDate(), mm = dob.getMonth() + 1, yyyy = dob.getFullYear();
                    dd = (dd < 10) ? '0' + dd : dd;
                    mm = (mm < 10) ? '0' + mm : mm;
                    dob_format = dd + '-' + mm + '-' + yyyy;
                }
                return {
                    'method': this.item.method,
                    'po_number': null,
                    'additional_data': {
                        "kvknummer": this.kvknummer,
                        "vatnumber": this.vatnumber,
                        "dob": dob_format,
                        "billink_agree": this.billink_agree,
                        "payment_option": this.paymentOption
                    }
                };
            },
            afterPlaceOrder: function () {
                window.location.replace(url.build('storekeeper_payment/checkout/redirect'));
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }

                var objButton = $(event.target);
                if (objButton.length > 0) {
                    if (objButton.is('span')) {
                        objButton = objButton.parent();
                    }
                    var curText = objButton.text();
                    objButton.text($.mage.__('Processing')).prop('disabled', true);
                }
                var placeOrder;
                this.isPlaceOrderActionAllowed(false);
                placeOrder = placeOrderAction(this.getData(), this.redirectAfterPlaceOrder);
                $.when(placeOrder).fail(function () {
                    if (objButton.length > 0) {
                        objButton.text(curText).prop('disabled', false);
                    }
                    this.isPlaceOrderActionAllowed(true);
                }.bind(this)).done(this.afterPlaceOrder.bind(this));
                return true;
            },
            getPaymentMethodsList: function () {
                var storekeeperPaymentMethodsList = [];
                var storekeeperPaymentMethods = window.checkoutConfig.storekeeper_payment_methods;
                $.each(storekeeperPaymentMethods, function (id, data) {
                    storekeeperPaymentMethodsList.push(data)
                })
                return storekeeperPaymentMethodsList;
            }
        });
    }
);
