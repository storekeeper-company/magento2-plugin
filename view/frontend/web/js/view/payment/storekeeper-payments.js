define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'storekeeperpayment',
        component: 'StoreKeeper_StoreKeeper/js/view/payment/method-renderer/storekeeperpayment'
    });

    return Component.extend({});
});
