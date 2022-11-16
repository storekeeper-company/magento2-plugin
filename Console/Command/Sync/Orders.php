<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Shipping\Model\ShipmentNotifier;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use StoreKeeper\StoreKeeper\Helper\Config;

class Orders extends Command
{
    const STORES = 'stores';

    private State $state;

    private OrdersHelper $ordersHelper;

    /**
     * @param State $state
     * @param OrdersHelper $ordersHelper
     * @param string|null $name
     */
    public function __construct(
        State $state,
        OrdersHelper $ordersHelper,
        Config $configHelper,
        LoggerInterface $logger,
        string $name = null
    )
    {
        parent::__construct($name);

        $this->state = $state;
        $this->ordersHelper = $ordersHelper;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
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
                echo "  Skipping order sync: mode not allowed\n";
                return;
            }

            $output->writeln('<info>Start order sync</info>');
            $page = 1;
            $pageSize = 3;
            $current = 0;
            $orders = $this->ordersHelper->getOrders($storeId, $page, $pageSize);

            $output->writeln('<info>Number of Orders ' . $orders->getTotalCount() . '</info>');

            while ($current < $orders->getTotalCount()) {
                foreach ($orders as $order) {
                    try {
                        
                        if ($storeKeeperId = $this->ordersHelper->exists($order)) {
                            $this->ordersHelper->update($order, $storeKeeperId);
                        } else {
                            $this->ordersHelper->onCreate($order);
                        }
                    } catch(\Exception|\Error $e) {
                        $error = $e->getMessage() . "\n";
                        $error .= ($e->getFile() ?? '') . ' at line #' . ($e->getLine() ?? '');
                        $output->writeln('<error>' . $error . '</error>');
                        $this->logger->error($e->getMessage());
                    }
                }
                $current += count($orders);
                $page++;
                $orders = $this->ordersHelper->getOrders($storeId, $page, $pageSize);
            }

            $output->writeln('<info>Finish order sync</info>');
        } catch (\Exception|\Error $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error($e->getMessage());
        }
    }
}
