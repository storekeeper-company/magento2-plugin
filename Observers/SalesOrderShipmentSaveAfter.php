<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as ApiOrders;

class SalesOrderShipmentSaveAfter implements ObserverInterface
{
    private Json $json;
    private PublisherInterface $publisher;
    private ApiOrders $apiOrders;

    /**
     * Constructor
     *
     * @param Json $json
     * @param PublisherInterface $publisher
     * @param ApiOrders $apiOrders
     */
    public function __construct(
        Json $json,
        PublisherInterface $publisher,
        ApiOrders $apiOrders
    ) {
        $this->json = $json;
        $this->publisher = $publisher;
        $this->apiOrders = $apiOrders;
    }

    /**
     * Sync Magento shipment with StoreKeeper
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $storekeeperId = $order->getStorekeeperId();

        if ($order->getStorekeeperId() && !$order->getStorekeeperShipmentId()) {
            $shipmentData = [
                'order_id' => $order->getId(),
                'storekeeper_id' => $storekeeperId,
                'store_id' => $order->getStoreId(),
                'items' => $this->getShipmentsItems($order->getStoreId(), $storekeeperId)
            ];

            $this->publisher->publish(
                \StoreKeeper\StoreKeeper\Model\OrderSync\ShipmentConsumer::CONSUMER_NAME,
                $this->json->serialize($shipmentData)
            );
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
