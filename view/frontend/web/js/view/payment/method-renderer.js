define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'storekeeper_payment',
                component: 'StoreKeeper_StoreKeeper/js/view/payment/method-renderer/storekeeper_payment'
            }
        );
        return Component.extend({});
    }
);
