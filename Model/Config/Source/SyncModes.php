<?php

namespace StoreKeeper\StoreKeeper\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

use StoreKeeper\StoreKeeper\Helper\Config;

class SyncModes implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            [
                'value' => Config::SYNC_NONE,
                'label' => __('None')
            ],
            [
                'value' => Config::SYNC_PRODUCTS,
                'label' => __('Products Only')
            ],
            [
                'value' => Config::SYNC_ORDERS,
                'label' => __('Orders Only')
            ],
            [
                'value' => Config::SYNC_ALL,
                'label' => __('All')
            ]
        ];
    }
}
