<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\StoreKeeperPaymentMethod;

class ApplePay extends StoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment_applepay';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '2277';
}
