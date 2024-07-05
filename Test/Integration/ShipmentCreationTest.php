<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Observers\SalesOrderShipmentSaveAfter;

/**
 * @magentoDbIsolation enabled
 */
class ShipmentCreationTest extends AbstractTestCase
{

    const SHIPMENT_ID = 1;

    const SHIPMENT_QUEUE = [
        "order_id" => "1",
        "storekeeper_id" => "200",
        "store_id" => "1",
        "items" => [["id" => "999", "quantity" => 1]]
    ];

    protected $shipmentFactory;
    protected $shipmentRepository;
    protected $shipmentObserver;
    protected $json;
    protected $publisher;
    protected $apiOrdersMock;
    protected $taskLogCollectionFactory;
    protected $shipmentConsumer;
    protected $orderApiClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->shipmentFactory = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order\ShipmentFactory::class);
        $this->shipmentRepository = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order\ShipmentRepository::class);
        $this->taskLogCollectionFactory = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog\CollectionFactory::class);
        $this->json = Bootstrap::getObjectManager()->create(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->publisher = Bootstrap::getObjectManager()->create(\Magento\Framework\MessageQueue\PublisherInterface::class);
        $this->orderResource = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->apiOrdersMock = $this->createMock(\StoreKeeper\StoreKeeper\Helper\Api\Orders::class);
        $this->orderApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\OrderApiClient::class);

        $this->orderApiClientMock->method('newOrderShipment')->willReturn(self::SHIPMENT_ID);
        $this->apiOrdersMock->method('getStoreKeeperOrder')->willReturn(
            ['order_items' => [0 => ['is_shipping' => false, 'id' => 999, 'quantity' => 1]]]
        );

        $this->shipmentObserver = $objectManager->getObject(
            SalesOrderShipmentSaveAfter::class,
            [
                'json' => $this->json,
                'publisher' => $this->publisher,
                'apiOrders' => $this->apiOrdersMock
            ]
        );

        $this->shipmentConsumer = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Model\OrderSync\ShipmentConsumer::class,
            [
                'orderApiClient' => $this->orderApiClientMock,
                'orderRepository' => $this->orderRepository,
                'orderResource' => $this->orderResource
            ]
        );
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/order_shipping.php
     * @magentoDbIsolation enabled
     */
    public function testShipmentCreation()
    {
        $existingOrder = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');
        $shipment = $existingOrder->getShipmentsCollection()->getFirstItem();

        //Mock and trigger shipment_save_after event with current order shipment record
        $observer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observer->method('getEvent')->willReturn(new Event());
        $observer->getEvent()->setData('shipment', $shipment);
        $this->shipmentObserver->execute($observer);

        //Verify that publisher inside observer event worked and added message with shipment data to queue
        $taskLogCollection = $this->taskLogCollectionFactory->create();
        $taskLogCollection->addFieldToSelect('body');
        $taskLogCollection->addFieldToFilter('topic_name', \StoreKeeper\StoreKeeper\Model\OrderSync\ShipmentConsumer::CONSUMER_NAME);
        $taskBody = $taskLogCollection->getLastItem()->getBody();

        //Assert message body
        $this->assertNotEmpty($taskBody);

        //Run shipment consumer with message body generated during shipment_save_after observer event
        $this->shipmentConsumer->process($taskBody);

        //Reload order with fresh data
        $existingOrder = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');

        //Assert that storekeeper shipment id were generated and assigned to order during consumer run
        $this->assertEquals(self::SHIPMENT_ID, $existingOrder->getStorekeeperShipmentId());
    }
}
