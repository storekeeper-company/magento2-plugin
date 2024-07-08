<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as ApiOrders;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Model\OrderSync\Shipment;

class SalesOrderShipmentSaveBefore implements ObserverInterface
{
    private Json $json;
    private ApiOrders $apiOrders;
    private Shipment $shipment;
    private Logger $logger;

    /**
     * Constructor
     *
     * @param Json $json
     * @param ApiOrders $apiOrders
     * @param Shipment $shipment
     * @param Logger $logger
     */
    public function __construct(
        Json $json,
        ApiOrders $apiOrders,
        Shipment $shipment,
        Logger $logger
    ) {
        $this->json = $json;
        $this->apiOrders = $apiOrders;
        $this->shipment = $shipment;
        $this->logger = $logger;
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

        if ($order->getStorekeeperId() && !$order->getStorekeeperShipmentId() && !$order->getOrderDetached()) {
            $shipmentData = [
                'order_id' => $order->getId(),
                'storekeeper_id' => $storekeeperId,
                'store_id' => $order->getStoreId(),
                'items' => $this->getShipmentsItems($order->getStoreId(), $storekeeperId)
            ];

            try{
                $serializedData = $this->json->serialize($shipmentData);
                $this->shipment->process($serializedData);
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
