<?php

namespace StoreKeeper\StoreKeeper\Model\PaymentMethods;

class Ideal extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'storekeeper_payment_ideal';

    /**
     * StoreKeeper Payment Method eId
     *
     * @var string
     */
    protected $_eId = '10';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->_code;
    }

    /**
     * @return string
     */
    public function getEId(): string
    {
        return $this->_eId;
    }
}
