<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use StoreKeeper\StoreKeeper\Helper\Config;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrderFactory;
use StoreKeeper\StoreKeeper\Model\ResourceModel\StoreKeeperFailedSyncOrder as StoreKeeperFailedSyncOrderResourceModel;

class Orders extends Command
{
    const STORES = 'stores';

    private State $state;

    private OrdersHelper $ordersHelper;

    private StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder;

    private StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource;

    /**
     * Orders constructor.
     * @param State $state
     * @param OrdersHelper $ordersHelper
     * @param Config $configHelper
     * @param LoggerInterface $logger
     * @param StoreKeeperFailedSyncOrderResourceModel $storeKeeperFailedSyncOrderResource
     * @param StoreKeeperFailedSyncOrderFactory $storeKeeperFailedSyncOrder
     * @param string|null $name
     */
    public function __construct(
        State $state,
        OrdersHelper $ordersHelper,
        Config $configHelper,
        LoggerInterface $logger,
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
     * @return void
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
                    try {
                        if ($this->configHelper->isDebugLogs($storeId)) {
                            $this->logger->info('Processing order: '.$order->getId());
                        }
                        if ($storeKeeperId = $this->ordersHelper->exists($order)) {
                            $this->ordersHelper->update($order, $storeKeeperId);
                        } else {
                            $this->ordersHelper->onCreate($order);
                        }
                    } catch(\Exception $e) {
                        $this->logger->error($e->getMessage());
                        $storeKeeperFailedSyncOrder = $this->storeKeeperFailedSyncOrder->create();
                        $this->storeKeeperFailedSyncOrderResource->load(
                            $storeKeeperFailedSyncOrder,
                            $order->getId(),
                            'order_id'
                        );
                        if (!$storeKeeperFailedSyncOrder->hasData('order_id')) {
                            $storeKeeperFailedSyncOrder->setOrderId((int)$order->getId());
                            $storeKeeperFailedSyncOrder->setIsFailed(1);
                            $this->storeKeeperFailedSyncOrderResource->save($storeKeeperFailedSyncOrder);
                        }

//                        $orderId = (int)$order->getId();
//                        $connection = $this->storeKeeperFailedSyncOrderResource->getConnection();
//
//                        $select = $connection->select()
//                            ->from($this->storeKeeperFailedSyncOrderResource->getMainTable(), 'order_id')
//                            ->where('order_id = ?', $orderId);
//
//                        if (!$connection->fetchOne($select)) {
//                            $data = [
//                                'order_id' => $orderId,
//                                'is_failed' => 1,
//                            ];
//                            $connection->insert($this->storeKeeperFailedSyncOrderResource->getMainTable(), $data);
//                        }

                    }
                }
                $current += count($orders);
                $page++;
                $orders = $this->ordersHelper->getOrders($storeId, $page, $pageSize);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
