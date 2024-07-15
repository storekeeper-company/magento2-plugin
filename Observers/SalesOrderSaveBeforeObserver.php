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
    private ApiOrders $apiOrders;
    private Shipment $shipment;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     * @param Json $json
     * @param PublisherInterface $publisher
     * @param OrderRepositoryInterface $orderRepository
     * @param ApiOrders $apiOrders
     */
    public function __construct(
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher,
        OrderRepositoryInterface $orderRepository,
        ApiOrders $apiOrders,
        Shipment $shipment
    ) {
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->orderRepository = $orderRepository;
        $this->apiOrders = $apiOrders;
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

        if ($order->getId()) {
            if (
                $this->authHelper->isConnected($order->getStoreId())
                && $this->authHelper->isOrderSyncEnabled($order->getStoreId())
                && !$order->getOrderDetached()
            ) {
                $order = $observer->getEvent()->getOrder();
                $oldOrder = $this->orderRepository->get($order->getId());
                $oldStatus = $order->getOrigData('status');

                //Send order to sync only if status is updated and exist in sk mapping list
                if ($order->getStatus() != $oldStatus && in_array($order->getStatus(), ApiOrders::statusMapping())) {
                    $this->publisher->publish(
                        \StoreKeeper\StoreKeeper\Model\OrderSync\Consumer::CONSUMER_NAME,
                        $this->json->serialize(['orderId' => $order->getIncrementId()])
                    );

                    if ($order->getStatus() != $oldStatus && $order->getStatus() == Order::STATE_COMPLETE) {
                        $this->createShipment($order);
                    }
                }
            }
        }
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function createShipment(OrderInterface $order): void
    {
        $storekeeperId = $order->getStorekeeperId();

        if ($this->apiOrders->allowShipmentCreation($order)) {
            $shipmentData = [
                'order_id' => $order->getId(),
                'storekeeper_id' => $storekeeperId,
                'store_id' => $order->getStoreId(),
                'items' => $this->getShipmentsItems($order->getStoreId(), $storekeeperId)
            ];

            try {
                $serializedData = $this->json->serialize($shipmentData);
                $this->shipment->process($serializedData);
                $order->setStorekeeperShipmentId($storekeeperId);
            } catch (\Exception $e) {
                $message = 'Error while creating shipment, storekeeper order number: ' . $storekeeperId;
                $this->logger->error(
                    $message,
                    [
                        'error' =>  $this->logger->buildReportData($e),
                        'request' =>  $serializedData
                    ]
                );

                //Trigger exception in order to stop shipment creation and display message to admin
                throw new \Magento\Framework\Exception\LocalizedException(__($message));
            }
        }
    }
    /**
     * @param string $storeId
     * @param string $storekeeperId
     * @return array
     */
    private function getShipmentsItems(string $storeId, string $storekeeperId): array
    {
        $storeKeeperOrder = $this->apiOrders->getStoreKeeperOrder($storeId, $storekeeperId);
        $shipmentItems = [];

        if ($storeKeeperOrder) {
            if (array_key_exists('order_items', $storeKeeperOrder)) {
                foreach ($storeKeeperOrder['order_items'] as $orderItem) {
                    if ($orderItem['is_shipping'] === false) {
                        $shipmentItems[] = [
                            'id' => $orderItem['id'],
                            'quantity' => $orderItem['quantity']
                        ];
                    }
                }
            }
        }

        return $shipmentItems;
    }
}
