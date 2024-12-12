<?php

namespace StoreKeeper\StoreKeeper\Model\OrderSync;

use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Helper\Api\Orders;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrderResourceModel;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrder;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrderFactory;

/**
 * Class Consumer used to process OperationInterface messages.
 */
class Consumer
{
    const CONSUMER_NAME = "storekeeper.queue.sync.orders";
    const QUEUE_NAME = "storekeeper.queue.sync.orders";
    private Orders $ordersHelper;
    private Config $configHelper;
    private Logger $logger;
    private Auth $authHelper;
    private StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource;
    private StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder;
    private StoreManagerInterface $storeManager;
    private OrderRepositoryInterface $orderRepository;
    private OrderInterfaceFactory $orderFactory;

    /**
     * Constructor
     *
     * @param Orders $ordersHelper
     * @param Config $configHelper
     * @param Logger $logger
     * @param Auth $authHelper
     * @param StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource
     * @param StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder
     * @param StoreManagerInterface $storeManager
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Orders $ordersHelper,
        Config $configHelper,
        Logger $logger,
        Auth $authHelper,
        StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource,
        StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository,
        OrderInterfaceFactory $orderFactory
    ) {
        $this->ordersHelper = $ordersHelper;
        $this->logger = $logger;
        $this->authHelper = $authHelper;
        $this->configHelper = $configHelper;
        $this->storeKeeperFailedSyncOrderResource = $storeKeeperFailedSyncOrderResource;
        $this->storeKeeperFailedSyncOrder = $storeKeeperFailedSyncOrder;
        $this->storeManager = $storeManager;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
    }

    /**
     * Process
     *
     * @param string $request
     * @return void
     */
    public function process($request): void
    {
        $data = json_decode($request, true);

        try {
            $orderId = $data['orderId'] ?? null;

            if (is_null($orderId)) {
                throw new \Exception("Missing order ID");
            }
            $order = $this->orderFactory->create()->loadByIncrementId($orderId);
            $storeId = $order->getStoreId();

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
                $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
                if (!$storeKeeperFailedSyncOrder->hasData('order_id')) {
                    $storeKeeperFailedSyncOrder->setOrderId($order->getId());
                    $storeKeeperFailedSyncOrder->setIsFailed(1);
                    $storeKeeperFailedSyncOrder->setExceptionMessage($e->getMessage());
                    $order->setStorekeeperOrderLastSync(time());
                    $order->setStorekeeperOrderPendingSync(0);
                    $order->setStorekeeperOrderPendingSyncSkip(true);
                    $this->orderRepository->save($order);
                } else {
                    $storeKeeperFailedSyncOrder->setUpdatedAt(time());
                }
                $this->storeKeeperFailedSyncOrderResource->save($storeKeeperFailedSyncOrder);
            }
        } catch (\Exception $e) {
            $this->logger->error("[{$orderId}]: {$e->getMessage()}", $this->logger->buildReportData($e));
        }
    }

    /**
     * @param string $orderId
     * @return StoreKeeperFailedSyncOrder
     */
    private function getStoreKeeperFailedSyncOrder(string $orderId): StoreKeeperFailedSyncOrder
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
