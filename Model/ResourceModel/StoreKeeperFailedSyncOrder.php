<?php

namespace StoreKeeper\StoreKeeper\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class StoreKeeperFailedSyncOrder extends AbstractDb
{
    /**
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    protected function _construct()
    {
        $this->_init('storekeeper_failed_sync_order', 'order_id');
    }
}
