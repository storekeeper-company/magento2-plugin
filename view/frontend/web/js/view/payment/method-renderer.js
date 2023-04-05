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
            {type: 'storekeeper_payment_billink', component: defaultComponent},
            {type: 'storekeeper_payment_blik', component: defaultComponent},
            {type: 'storekeeper_payment_cartebleue', component: defaultComponent},
            {type: 'storekeeper_payment_creditclick', component: defaultComponent},
            {type: 'storekeeper_payment_ideal', component: defaultComponent},
            {type: 'storekeeper_payment', component: defaultComponent}
        ];
        $.each(methods, function (key, method) {
            rendererList.push(method);
        });
        return Component.extend({});
    }
);
