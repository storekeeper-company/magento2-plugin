<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\StoreKeeperPaymentMethod;

class PaymentMethod extends StoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '';
}
