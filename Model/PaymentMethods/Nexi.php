<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\AbstractStoreKeeperPaymentMethod;

class Nexi extends AbstractStoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment_nexi';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '1945';
}
