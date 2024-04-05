<?php

namespace StoreKeeper\StoreKeeper\Cron;

use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrderFactory;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrder;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrderResourceModel;
use Magento\Sales\Api\OrderRepositoryInterface;

class Orders
{
    const STORES = 'stores';
    private OrdersHelper $ordersHelper;
    private Config $configHelper;
    private LoggerInterface $logger;
    private StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource;
    private StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder;
    private StoreManagerInterface $storeManager;
    private OrderRepositoryInterface $orderRepository;


    /**
     * Orders constructor
     *
     * @param OrdersHelper $ordersHelper
     * @param Config $configHelper
     * @param LoggerInterface $logger
     * @param StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource
     * @param StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder
     * @param string|null $name
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrdersHelper $ordersHelper,
        Config $configHelper,
        LoggerInterface $logger,
        StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource,
        StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->ordersHelper = $ordersHelper;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->storeKeeperFailedSyncOrderResource = $storeKeeperFailedSyncOrderResource;
        $this->storeKeeperFailedSyncOrder = $storeKeeperFailedSyncOrder;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $stores = $this->storeManager->getStores();

            foreach ($stores as $store) {
                $storeId = $store->getId();

                if (!$this->configHelper->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_ALL)) {
                    return;
                }

                $page = 1;
                $pageSize = 25;
                $current = 0;
                $orders = $this->ordersHelper->getOrders($storeId, $page, $pageSize);

                while ($current < $orders->getTotalCount()) {
                    foreach ($orders as $order) {
                        $orderId = $order->getId();
                        $storeKeeperFailedSyncOrder = $this->getStoreKeeperFailedSyncOrder($orderId);
                        try {
                            if ($this->configHelper->isDebugLogs($storeId)) {
                                $this->logger->info('Processing order: ' . $orderId);
                            }
                            if ($storeKeeperId = $this->ordersHelper->exists($order)) {
                                $this->ordersHelper->update($order, $storeKeeperId);
                            } else {
                                $this->ordersHelper->onCreate($order);
                            }
                            if ($storeKeeperFailedSyncOrder->hasData('order_id')) {
                                $storeKeeperFailedSyncOrder->setIsFailed(0);
                                $this->storeKeeperFailedSyncOrderResource->save($storeKeeperFailedSyncOrder);
                            }
                        } catch(\Exception $e) {
                            $this->logger->error($e->getMessage());
                            if (!$storeKeeperFailedSyncOrder->hasData('order_id')) {
                                $storeKeeperFailedSyncOrder->setOrderId((int)$orderId);
                                $storeKeeperFailedSyncOrder->setIsFailed(1);
                                $order->setStorekeeperOrderLastSync(time());
                                $order->setStorekeeperOrderPendingSync(0);
                                $order->setStorekeeperOrderPendingSyncSkip(true);
                                $this->orderRepository->save($order);
                            } else {
                                $storeKeeperFailedSyncOrder->setUpdatedAt(time());
                            }
                            $this->storeKeeperFailedSyncOrderResource->save($storeKeeperFailedSyncOrder);
                        }
                    }
                    $current += count($orders);
                    $page++;
                    $orders = $this->ordersHelper->getOrders($storeId, $page, $pageSize);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param string $orderId
     * @return StoreKeeperFailedSyncOrder
     */
    private function getStoreKeeperFailedSyncOrder(string $orderId):StoreKeeperFailedSyncOrder
    {
        $storeKeeperFailedSyncOrder = $this->storeKeeperFailedSyncOrder->create();
        $this->storeKeeperFailedSyncOrderResource->load(
            $storeKeeperFailedSyncOrder,
            $orderId,
            'order_id'
        );

        return $storeKeeperFailedSyncOrder;
    }
}
