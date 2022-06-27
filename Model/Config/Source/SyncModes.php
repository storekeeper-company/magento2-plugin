<?php

namespace StoreKeeper\StoreKeeper\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SyncModes implements OptionSourceInterface
{

    public function toOptionArray()
    {
        return [
            [
                'value' => 0,
                'label' => __('Default')
            ],
            [
                'value' => 1,
                'label' => __('Order only mode')
            ]
        ];
    }
}
