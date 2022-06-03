<?php

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Orders extends Command
{
    const STORES = 'stores';

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var State
     */
    private $state;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Auth
     */
    private $authHelper;

    /**
     * @var \OrdersHelper
     */
    private $ordersHelper;

    public function __construct(
        State $state,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        Auth $authHelper,
        OrdersHelper $ordersHelper,
        string $name = null
    ) {
        parent::__construct($name);

        $this->state = $state;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->authHelper = $authHelper;
        $this->ordersHelper = $ordersHelper;
    }

    protected function configure()
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

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $storeId = $input->getOption(self::STORES);

        $output->writeln('<info>Start order sync</info>');
        $orders = $this->getOrders($storeId);
        $output->writeln('<info>' . $orders->getTotalCount() . '</info>');
        foreach ($orders as $order) {
            /** @var $order Order */
            $payload = $this->ordersHelper->prepareOrder($order, $storeId);
            if ($order->getStorekeeperId() > 0) {
                $storeKeeperOrder = $this->authHelper->getModule('ShopModule', $storeId)->getOrder($order->getStorekeeperId());
                $statusMapping = $this->statusMapping();

                if ($statusMapping[$storeKeeperOrder['status']] !== $order->getStatus()) {
                    $output->writeln('<info>Updated StoreKeeper Order status</info>');
                    $this->authHelper->getModule('ShopModule', $storeId)->updateOrderStatus(['status' => array_search($order->getStatus(), $statusMapping)], $order->getStorekeeperId());
                } else {
                    $output->writeln('<info>Update StoreKeeper Order with StoreKeeper id:' . $order->getStorekeeperId() . '</info>');
                    $this->authHelper->getModule('ShopModule', $storeId)->updateOrder($payload, $order->getStorekeeperId());
                }
            } else {
                $output->writeln('<info>Create Order with id: ' . $order->getId() . '</info>');
                $storekeeper_id = $this->authHelper->getModule('ShopModule', $storeId)->newOrder($payload);
                $order->setStorekeeperId($storekeeper_id);
                $this->orderRepository->save($order);
            }
        }
    }

    private function getOrders($storeId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'store_id',
                $storeId,
                'eq'
            )
            ->addFilter(
                'status',
                ['processing', 'canceled'],
                'in'
            )
            ->create();

        return $this->orderRepository->getList($searchCriteria);
    }

    private function statusMapping()
    {
        return [
            'complete' => 'completed',
            'processing' => 'processing',
            'cancelled' => 'canceled',
            'new' => 'new'
        ];
    }
}
