<?php

namespace StoreKeeper\StoreKeeper\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;

class DataProviderCollection
{
    /**
     * After get report
     *
     * @param CollectionFactory $subject
     * @param $collection
     * @param $requestName
     * @return mixed
     */
    public function afterGetReport(CollectionFactory $subject, $collection, $requestName)
    {
        if (str_contains($requestName, 'sales_order_grid_data_source')) {
            $collection->getSelect()->joinLeft
            (
                ['skfso' => $collection->getTable('storekeeper_failed_sync_order')],
                'main_table.entity_id = skfso.order_id',
                ['is_failed']

            );
        }

        return $collection;
    }
}
