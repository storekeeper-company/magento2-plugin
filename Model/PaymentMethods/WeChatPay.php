<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\AbstractStoreKeeperPaymentMethod;

class WeChatPay extends AbstractStoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
        protected $_code = 'storekeeper_payment_wechatpay';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '1978';
}
