define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var defaultComponent = 'StoreKeeper_StoreKeeper/js/view/payment/method-renderer/storekeeper_payment';
        var methods = [
            {type: 'storekeeper_payment_alipay', component: defaultComponent},
            {type: 'storekeeper_payment_amex', component: defaultComponent},
            {type: 'storekeeper_payment_applepay', component: defaultComponent},
            {type: 'storekeeper_payment_bataviacadeaukaart', component: defaultComponent},
            {type: 'storekeeper_payment_billink', component: defaultComponent},
            {type: 'storekeeper_payment_blik', component: defaultComponent},
            {type: 'storekeeper_payment_cartebleue', component: defaultComponent},
            {type: 'storekeeper_payment_creditclick', component: defaultComponent},
            {type: 'storekeeper_payment_dankort', component: defaultComponent},
            {type: 'storekeeper_payment_eps', component: defaultComponent},
            {type: 'storekeeper_payment_fashioncheque', component: defaultComponent},
            {type: 'storekeeper_payment_fashiongiftcard', component: defaultComponent},
            {type: 'storekeeper_payment_gezondheidsbon', component: defaultComponent},
            {type: 'storekeeper_payment_giropay', component: defaultComponent},
            {type: 'storekeeper_payment_givacard', component: defaultComponent},
            {type: 'storekeeper_payment_ideal', component: defaultComponent},
            {type: 'storekeeper_payment_maestro', component: defaultComponent},
            {type: 'storekeeper_payment_multibanco', component: defaultComponent},
            {type: 'storekeeper_payment_nexi', component: defaultComponent},
            {type: 'storekeeper_payment_overboeking', component: defaultComponent},
            {type: 'storekeeper_payment_payconiq', component: defaultComponent},
            {type: 'storekeeper_payment_paypal', component: defaultComponent},
            {type: 'storekeeper_payment_paysafecard', component: defaultComponent},
            {type: 'storekeeper_payment_postepay', component: defaultComponent},
            {type: 'storekeeper_payment_przelewy24', component: defaultComponent},
            {type: 'storekeeper_payment_spraypay', component: defaultComponent},
            {type: 'storekeeper_payment_telefonischbetalen', component: defaultComponent},
            {type: 'storekeeper_payment_visamastercard', component: defaultComponent},
            {type: 'storekeeper_payment_wechatpay', component: defaultComponent},
            {type: 'storekeeper_payment_yourgift', component: defaultComponent},
            {type: 'storekeeper_payment', component: defaultComponent}
        ];
        $.each(methods, function (key, method) {
            rendererList.push(method);
        });
        return Component.extend({});
    }
);
