<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\OrderSync;

use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;

class Shipment
{
    private OrderApiClient $orderApiClient;
    private OrderResource $orderResource;
    private OrderRepository $orderRepository;

    /**
     * Constructor
     *
     * @param Logger $logger
     * @param OrderApiClient $orderApiClient
     * @param OrderResource $orderResource
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderApiClient $orderApiClient,
        OrderResource $orderResource,
        OrderRepository $orderRepository
    ) {
        $this->orderApiClient = $orderApiClient;
        $this->orderResource = $orderResource;
        $this->orderRepository = $orderRepository;
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

        $shipmentId = $this->orderApiClient->newOrderShipment(
            $data['storekeeper_id'],
            $data['items'],
            $data['store_id']
        );

        $this->orderApiClient->markOrderShipmentDelivered($data['store_id'], $shipmentId);

        $order = $this->orderRepository->get($data['order_id']);
        $order->setData('storekeeper_shipment_id', $shipmentId);
        $this->orderResource->saveAttribute($order, 'storekeeper_shipment_id');
    }
}
