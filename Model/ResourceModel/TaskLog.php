<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class TaskLog extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('storekeeper_storekeeper_tasklog', 'tasklog_id');
    }
}
