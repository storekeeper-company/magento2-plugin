<?php

namespace StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrder;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrdersResourceModel;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'order_id';

    protected function _construct()
    {
        $this->_init(
            StoreKeeperFailedSyncOrder::class,
            StoreKeeperFailedSyncOrdersResourceModel::class
        );
    }
}
