<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\ResourceModel\EventLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'eventlog_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \StoreKeeper\StoreKeeper\Model\EventLog::class,
            \StoreKeeper\StoreKeeper\Model\ResourceModel\EventLog::class
        );
    }
}

