<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Order;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use StoreKeeper\StoreKeeper\Console\Command\Sync\Orders;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrderResourceModel;

class MassResyncFailedOrders extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction implements HttpPostActionInterface
{
    private Orders $syncOrders;

    private Config $configHelper;

    private OrdersHelper $ordersHelper;

    private LoggerInterface $logger;

    private StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource;

    /**
     * MassResyncFailedOrders constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param Orders $syncOrders
     * @param Config $configHelper
     * @param OrdersHelper $ordersHelper
     * @param LoggerInterface $logger
     * @param StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        Orders $syncOrders,
        Config $configHelper,
        OrdersHelper $ordersHelper,
        LoggerInterface $logger,
        StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->syncOrders = $syncOrders;
        $this->configHelper = $configHelper;
        $this->ordersHelper = $ordersHelper;
        $this->logger = $logger;
        $this->storeKeeperFailedSyncOrderResource = $storeKeeperFailedSyncOrderResource;
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection): \Magento\Backend\Model\View\Result\Redirect
    {
        $syncedOrderIds = [];
        $failedOrderIds = [];
        foreach ($collection->getItems() as $order) {
            $orderId = $order->getId();
            $storeKeeperFailedSyncOrder = $this->syncOrders->getStoreKeeperFailedSyncOrder($orderId);
            try {
                if ($this->configHelper->isDebugLogs($order->getStoreId())) {
                    $this->logger->info('Processing order: ' . $orderId);
                }
                if ($storeKeeperId = $this->ordersHelper->exists($order)) {
                    $this->ordersHelper->update($order, $storeKeeperId);
                } else {
                    $this->ordersHelper->onCreate($order);
                }
                $syncedOrderIds[] = $orderId;
                $storeKeeperFailedSyncOrder->setIsFailed(0);
                $storeKeeperFailedSyncOrder->setUpdatedAt(time());
                $this->storeKeeperFailedSyncOrderResource->save($storeKeeperFailedSyncOrder);
            } catch(\Exception $e) {
                $failedOrderIds[] = $orderId;
                $this->logger->error($e->getMessage());
                $storeKeeperFailedSyncOrder->setUpdatedAt(time());
                $this->storeKeeperFailedSyncOrderResource->save($storeKeeperFailedSyncOrder);
            }
        }

        if ($failedOrderIds) {
            $failedOrderIdsString = implode(', ', $failedOrderIds);
            $failedMessage = count($failedOrderIds) > 1
                ? 'Orders IDs: ' . $failedOrderIdsString . ' were not synced.'
                : 'Order ID ' . $failedOrderIdsString . ' was not synced.';
            $this->messageManager->addErrorMessage($failedMessage);
        }

        if ($syncedOrderIds) {
            $syncedOrderIdsString = implode(', ', $syncedOrderIds);
            $syncedMessage = count($syncedOrderIds) > 1
                ? 'Orders IDs: ' . $syncedOrderIdsString . ' were synced.'
                : 'Order ID ' . $syncedOrderIdsString . ' was synced.';
            $this->messageManager->addSuccessMessage($syncedMessage);
        }


        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());

        return $resultRedirect;
    }
}
