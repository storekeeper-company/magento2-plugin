<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\AbstractStoreKeeperPaymentMethod;

class CarteBleue extends AbstractStoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment_cartebleue';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '710';
}
