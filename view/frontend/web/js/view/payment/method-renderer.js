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
            {type: 'storekeeper_payment_ideal', component: defaultComponent},
            {type: 'storekeeper_payment', component: defaultComponent}
        ];
        $.each(methods, function (key, method) {
            rendererList.push(method);
        });
        return Component.extend({});
    }
);
