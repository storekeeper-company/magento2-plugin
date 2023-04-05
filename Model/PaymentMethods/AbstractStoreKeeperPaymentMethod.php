<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

use Magento\Payment\Model\Method\AbstractMethod;

abstract class AbstractStoreKeeperPaymentMethod extends AbstractMethod
{
    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @return string
     */
    public function getEId(): string
    {
        return $this->_eId;
    }
}
