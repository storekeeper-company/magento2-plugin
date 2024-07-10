<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\Event;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * @magentoDbIsolation enabled
 */
class OrderWithInvoiceCreationTest extends AbstractTestCase
{
    protected $invoice;
    protected $orderResource;
    protected $invoiceObserver;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->invoice = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Service\InvoiceService::class);
        $this->orderResource = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\ResourceModel\Order::class);
        $this->paymentApiClientMock->method('isStorekeeperPayment')->willReturn(false);
        $this->invoiceObserver = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Observers\SalesOrderInvoicePayObserver::class,
            [
                'orderResource' => $this->orderResource,
                'paymentApiClient' => $this->paymentApiClientMock,
                'authHelper' => $this->authHelper,
            ]
        );
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/order.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     */
    public function testOrderCreation()
    {
        $order = $this->orderFactory->create()->loadByIncrementId('100000001');
        $invoice = $this->invoice->prepareInvoice($order);
        $invoice->register();
        $invoice->save();
        $order->setData('storekeeper_id', '17')->save();

        //Mock observer run with freshly created invoice
        $observer = $this->getMockBuilder(Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $observer->method('getEvent')->willReturn(new Event());
        $observer->getEvent()->setData('invoice', $invoice);
        $this->invoiceObserver->execute($observer);

        //Reload order with fresh data
        $existingOrder = $this->orderFactory->create()->loadByIncrementId('100000001');
        //Assert that storekeeper_payment_id was populated during 'storekeeper_sales_order_invoice_pay' event
        $this->assertNotEmpty($existingOrder->getStorekeeperPaymentId());
    }
}
