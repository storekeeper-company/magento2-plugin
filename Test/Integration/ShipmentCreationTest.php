<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation enabled
 */
class ShipmentCreationTest extends AbstractTestCase
{

    const SHIPMENT_ID = 200;

    const SHIPMENT_QUEUE = [
        "order_id" => "1",
        "storekeeper_id" => "200",
        "store_id" => "1",
        "items" => [["id" => "999", "quantity" => 1]]
    ];

    protected $shipmentFactory;
    protected $shipmentRepository;
    protected $orderObserver;
    protected $json;
    protected $apiOrdersMock;
    protected $shipment;
    protected $orderApiClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->shipmentFactory = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order\ShipmentFactory::class);
        $this->shipmentRepository = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order\ShipmentRepository::class);
        $this->json = Bootstrap::getObjectManager()->create(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->apiOrdersMock = $this->createMock(\StoreKeeper\StoreKeeper\Helper\Api\Orders::class);
        $this->orderApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\OrderApiClient::class);

        $this->orderApiClientMock->method('newOrderShipment')->willReturn(self::SHIPMENT_ID);
        $this->apiOrdersMock->method('getStoreKeeperOrder')->willReturn(
            ['order_items' => [0 => ['is_shipping' => false, 'id' => 999, 'quantity' => 1]]]
        );
        $this->apiOrdersMock->method('allowShipmentCreation')->willReturn(true);

        $this->shipment = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Model\OrderSync\Shipment::class,
            [
                'orderApiClient' => $this->orderApiClientMock,
                'orderRepository' => $this->orderRepository,
                'orderResource' => $this->orderResource,
                'apiOrders' => $this->apiOrdersMock,
                'json' => $this->json
            ]
        );

        $this->orderObserver = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Observers\SalesOrderSaveBeforeObserver::class,
            [
                'json' => $this->json,
                'shipment' => $this->shipment,
                'authHelper' => $this->authHelper,
                'orderRepository' => $this->orderRepository
            ]
        );
    }

    /**
     * @magentoConfigFixture admin_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture admin_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture admin_store storekeeper_general/general/storekeeper_sync_mode 4
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/order_shipping.php
     * @magentoDbIsolation enabled
     */
    public function testShipmentCreation()
    {
        $existingOrder = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');

        //Mock and trigger order_save_before event with current order in complete state
        $existingOrder->setOrderDetached(false);
        $existingOrder->setStatus(\Magento\Sales\Model\Order::STATE_COMPLETE);
        $observer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observer->method('getEvent')->willReturn(new Event());
        $observer->getEvent()->setData('order', $existingOrder);
        $this->orderObserver->execute($observer);

        //Assert that storekeeper shipment id were generated and assigned to order during consumer run
        $this->assertEquals(self::SHIPMENT_ID, $existingOrder->getStorekeeperShipmentId());
    }
}
