<?php

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Framework\Model\AbstractModel;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrderResourceModel;

class StoreKeeperFailedSyncOrder extends AbstractModel
{

    /**
     * Constructed
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(StoreKeeperFailedSyncOrderResourceModel::class);
    }
}
