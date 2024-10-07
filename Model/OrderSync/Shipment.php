<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\OrderSync;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderRepository;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as ApiOrders;
use StoreKeeper\StoreKeeper\Logger\Logger;

class Shipment
{
    private OrderApiClient $orderApiClient;
    private OrderRepository $orderRepository;
    private ApiOrders $apiOrders;
    private Json $json;
    private Logger $logger;
    private ManagerInterface $messageManager;

    /**
     * Constructor
     *
     * @param OrderApiClient $orderApiClient
     * @param OrderRepository $orderRepository
     * @param ApiOrders $apiOrders
     * @param Json $json
     * @param Logger $logger
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        OrderApiClient $orderApiClient,
        OrderRepository $orderRepository,
        ApiOrders $apiOrders,
        Json $json,
        Logger $logger,
        ManagerInterface $messageManager
    ) {
        $this->orderApiClient = $orderApiClient;
        $this->orderRepository = $orderRepository;
        $this->apiOrders = $apiOrders;
        $this->json = $json;
        $this->logger = $logger;
        $this->messageManager = $messageManager;

    }

    /**
     * Process
     *
     * @param string $request
     * @return void
     * @throws \Exception
     */
    public function process(string $request): void
    {
        $data = json_decode($request, true);

        $shipmentId = $this->orderApiClient->newOrderShipment(
            $data['storekeeper_id'],
            $data['items'],
            $data['store_id']
        );

        $this->orderApiClient->markOrderShipmentDelivered($data['store_id'], $shipmentId);
    }

    /**
     * @param OrderInterface $order
     * @param $storeId
     * @return void
     */
    public function createShipment(OrderInterface $order, $storeId): void
    {
        $storekeeperId = $order->getStorekeeperId();

        if ($this->apiOrders->allowShipmentCreation($order)) {
            $shipmentData = [
                'order_id' => $order->getId(),
                'storekeeper_id' => $storekeeperId,
                'store_id' => $storeId,
                'items' => $this->getShipmentsItems($storeId, $storekeeperId)
            ];

            try {
                $serializedData = $this->json->serialize($shipmentData);
                $this->process($serializedData);
                $order->setStorekeeperShipmentId($storekeeperId);
            } catch (\Exception $e) {
                $message = 'Error synchronizing shipment to StoreKeeper! Storekeeper order number: ' . $storekeeperId;
                $this->logger->error(
                    $message,
                    [
                        'error' =>  $this->logger->buildReportData($e),
                        'request' =>  $serializedData
                    ]
                );

                $this->messageManager->addNoticeMessage(__($message));
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
