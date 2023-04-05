<?php

namespace StoreKeeper\StoreKeeper\Model\Config\Source\Available;

use Magento\Framework\Data\OptionSourceInterface;
use StoreKeeper\StoreKeeper\Model\PaymentMethods\AliPay as PaymentMethod;
use StoreKeeper\StoreKeeper\Model\Config\Source\Available\Available;

class AliPay implements OptionSourceInterface
{
    private PaymentMethod $paymentMethod;

    private Available $available;

    /**
     * AliPay constructor.
     * @param PaymentMethod $paymentMethod
     * @param Available $available
     */
    public function __construct(
        PaymentMethod $paymentMethod,
        Available $available
    ) {
        $this->paymentMethod = $paymentMethod;
        $this->available = $available;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray(): array
    {
        return $this->available->getOptions($this->paymentMethod->getCode());
    }
}
