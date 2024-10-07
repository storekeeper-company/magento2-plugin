<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as ApiOrders;
use StoreKeeper\StoreKeeper\Model\OrderSync\Shipment;

class SalesOrderSaveBeforeObserver implements ObserverInterface
{
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;
    private OrderRepositoryInterface $orderRepository;
    private Shipment $shipment;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     * @param Json $json
     * @param PublisherInterface $publisher
     * @param OrderRepositoryInterface $orderRepository
     * @param Shipment $shipment
     */
    public function __construct(
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher,
        OrderRepositoryInterface $orderRepository,
        Shipment $shipment
    ) {
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->orderRepository = $orderRepository;
        $this->shipment = $shipment;
    }

    /**
     * Set order as pending for sync
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $storeId = $this->authHelper->getStoreId($order->getStoreId());

        if ($order->getId()) {
            if (
                $this->authHelper->isConnected($storeId)
                && $this->authHelper->isOrderSyncEnabled($storeId)
                && !$order->getOrderDetached()
            ) {
                $order = $observer->getEvent()->getOrder();
                $oldOrder = $this->orderRepository->get($order->getId());
                $oldStatus = $oldOrder->getOrigData('status');

                //Send order to sync only if status is updated and exist in sk mapping list
                if ($order->getStatus() != $oldStatus && in_array($order->getStatus(), ApiOrders::statusMapping())) {
                    $this->publisher->publish(
                        \StoreKeeper\StoreKeeper\Model\OrderSync\Consumer::CONSUMER_NAME,
                        $this->json->serialize(['orderId' => $order->getIncrementId()])
                    );

                    if ($order->getStatus() == Order::STATE_COMPLETE) {
                        $this->shipment->createShipment($order, $storeId);
                    }
                }
            }
        }
    }
}
