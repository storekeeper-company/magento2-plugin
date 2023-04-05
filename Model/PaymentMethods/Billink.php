<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use StoreKeeper\StoreKeeper\Model\PaymentMethods\StoreKeeperPaymentMethod;

class Billink extends StoreKeeperPaymentMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment_billink';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '1672';
}
