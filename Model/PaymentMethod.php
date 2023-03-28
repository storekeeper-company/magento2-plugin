<?php

namespace StoreKeeper\StoreKeeper\Model;

class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;
}
