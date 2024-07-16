<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\OrderSync;

use Magento\Sales\Model\OrderRepository;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;

class Shipment
{
    private OrderApiClient $orderApiClient;
    private OrderRepository $orderRepository;

    /**
     * Constructor
     *
     * @param OrderApiClient $orderApiClient
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        OrderApiClient $orderApiClient,
        OrderRepository $orderRepository
    ) {
        $this->orderApiClient = $orderApiClient;
        $this->orderRepository = $orderRepository;
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
}
