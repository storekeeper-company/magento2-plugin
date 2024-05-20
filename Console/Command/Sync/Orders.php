<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use StoreKeeper\StoreKeeper\Helper\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrderFactory;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrder;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrderResourceModel;

class Orders extends Command
{
    const STORES = 'stores';
    private State $state;
    private OrdersHelper $ordersHelper;
    private Config $configHelper;
    private Logger $logger;
    private StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder;
    private StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource;

    /**
     * Orders constructor
     *
     * @param State $state
     * @param OrdersHelper $ordersHelper
     * @param Config $configHelper
     * @param Logger $logger
     * @param StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource
     * @param StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder
     * @param string|null $name
     */
    public function __construct(
        State $state,
        OrdersHelper $ordersHelper,
        Config $configHelper,
        Logger $logger,
        StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource,
        StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder,
        string $name = null
    ) {
        parent::__construct($name);

        $this->state = $state;
        $this->ordersHelper = $ordersHelper;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->storeKeeperFailedSyncOrderResource = $storeKeeperFailedSyncOrderResource;
        $this->storeKeeperFailedSyncOrder = $storeKeeperFailedSyncOrder;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName("storekeeper:sync:orders");
        $this->setDescription('Sync orders');
        $this->setDefinition([
            new InputOption(
                self::STORES,
                null,
                InputOption::VALUE_REQUIRED,
                'Store ID'
            )
        ]);

        parent::configure();
    }

    /**
     * Sync orders with StoreKeeper
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws LocalizedException
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    ) {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
            $storeId = $input->getOption(self::STORES);

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
                        $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
                        if (!$storeKeeperFailedSyncOrder->hasData('order_id')) {
                            $storeKeeperFailedSyncOrder->setOrderId((int)$orderId);
                            $storeKeeperFailedSyncOrder->setIsFailed(1);
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
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
        }
    }

    /**
     * Get failed sync orders
     *
     * @param string $orderId
     * @return StoreKeeperFailedSyncOrder
     */
    public function getStoreKeeperFailedSyncOrder(string $orderId):StoreKeeperFailedSyncOrder
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
