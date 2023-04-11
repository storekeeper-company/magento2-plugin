<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\AbstractStoreKeeperPaymentMethod;

class Przelewy24 extends AbstractStoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment_przelewy24';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '2151';
}
