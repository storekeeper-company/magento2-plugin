<?php

namespace StoreKeeper\StoreKeeper\Model\Config\Source\Available;

use StoreKeeper\StoreKeeper\Model\ConfigProvider;

class Available
{
    private ConfigProvider $configProvider;

    /**
     * Available constructor.
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param string $code
     * @return array[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOptions(string $code): array
    {
        $avaliablePaymentMethods = $this->configProvider->getMappedPaymentMethods();
        $isFound = false;

        foreach ($avaliablePaymentMethods as $item) {
            if (isset($item['magento_payment_method_code']) && $item['magento_payment_method_code'] == $code) {
                $isFound = true;
                break;
            }
        }

        $result = $isFound ? [
            [
                'value' => 0,
                'label' => __('No')
            ],
            [
                'value' => 1,
                'label' => __('Yes')
            ]
        ] : [
            [
                'value' => 0,
                'label' => __('Not available, you can enable this in StoreKeeper admin')
            ]
        ];

        return $result;
    }
}
