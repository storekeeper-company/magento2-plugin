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
                template: 'StoreKeeper_StoreKeeper/payment/storekeeper_payment',
                logo: '',
                paymentId: 0,
                paymentQty: 2
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
                window.location.replace(url.build('storekeeper_payment/checkout/redirect?storekeeper_payment_method_id=' + this.getPaymentId()));
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
                var magentoActivePaymentMethods = window.checkoutConfig.magento_active_payment_methods;
                $.each(storekeeperPaymentMethods, function (id, storeKeeperData) {
                    var isFound = false;
                    $.each(magentoActivePaymentMethods, function (id, magentoActivePayment) {
                        if (storeKeeperData.magento_payment_method_code == magentoActivePayment) {
                            isFound = true;
                        }
                    })
                    if (!isFound) {
                        storekeeperPaymentMethodsList.push(storeKeeperData)
                    }
                })
                return storekeeperPaymentMethodsList;
            },
            showSubmethods: function () {
                return this.item.method == 'storekeeper_payment';
            },
            getPaymentIcon: function () {
                var list = this.getPaymentMethodsList();
                list.forEach((item) => {
                    if (item.magento_payment_method_code == this.item.method) {
                        this.logo = item.storekeeper_payment_method_logo_url;
                    }
                })
                return this.logo;
            },
            getPaymentId: function () {
                var list = this.getPaymentMethodsList();
                list.forEach((item) => {
                    if (item.magento_payment_method_code == this.item.method) {
                        this.paymentId = item.storekeeper_payment_method_id;
                    }
                })
                return this.paymentId;
            },
            expand: function () {
                let self = this;
                this.container = $('.expanded-ul');
                this.el = $('li', this.container);

                if (this.el.length > this.paymentQty) {
                    let containerHeight = this.el.outerHeight() * this.paymentQty + 'px';
                    let loadMore = '<a href="#" class="load-more-btn" style="padding-left: 40px">Show more</a>';

                    this.container.addClass('collapsed').css({"maxHeight": containerHeight});

                    if (!$('.payment-method-title .load-more-btn').length) {
                        this.container.after(loadMore);

                        $('body').on('click', '.load-more-btn', function () {
                            if (self.container.hasClass('collapsed')) {
                                self.container.removeClass('collapsed').css({"maxHeight": 'none'});
                                $(this).text('Show Less');
                            } else {
                                self.container.addClass('collapsed').css({"maxHeight": containerHeight});
                                $(this).text('Show More');
                            }
                        });
                    }
                }
            }
        });
    }
);
