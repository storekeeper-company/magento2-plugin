<?php

namespace StoreKeeper\StoreKeeper\Model\ResourceModel\Order\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;
use StoreKeeper\StoreKeeper\Logger\Logger as Logger;

class Collection extends OriginalCollection
{
    /**
     * Collection constructor.
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        $mainTable = 'sales_order_grid',
        $resourceModel = \Magento\Sales\Model\ResourceModel\Order::class
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * @return void
     */
    protected function _renderFiltersBefore(): void
    {
        $joinTable = $this->getTable('storekeeper_failed_sync_order');
        $this->getSelect()->joinLeft($joinTable, 'main_table.entity_id = storekeeper_failed_sync_order.order_id', ['is_failed']);
        parent::_renderFiltersBefore();
    }
}
