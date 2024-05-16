<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'tasklog_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \StoreKeeper\StoreKeeper\Model\TaskLog::class,
            \StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog::class
        );
    }
}

