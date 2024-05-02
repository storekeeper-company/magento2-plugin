<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Creditmemo;

class WebhookTest extends AbstractTestCase
{
    const QUEUE_MESSAGE = '{"type":"updated","entity":"Order","storeId":"1","module":"ShopModule","key":"id","value":"55","refund":true}';
    const QUEUE_MESSAGE_STOCK_CHANGE = '{"type":"stock_change","entity":"Product","storeId":"1","module":"ShopModule","key":"id","value":"55"}';
    const SUCCESS_JSON_RESPONSE_MESSAGE = '{"success":true,"message":"Processing entity \u0022Order\u0022"}';

    protected $webhook;
    protected $requestMock;
    protected $json;
    protected $publisher;
    protected $consumer;
    protected $jsonResponse;
    protected $productsHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->requestMock = $this->createMock(\Magento\Framework\Webapi\Rest\Request::class);
        $this->json = Bootstrap::getObjectManager()->create(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->publisher = Bootstrap::getObjectManager()->create(\Magento\Framework\MessageQueue\PublisherInterface::class);
        $this->jsonResponse = Bootstrap::getObjectManager()->create(\Symfony\Component\HttpFoundation\JsonResponse::class);

        $this->requestMock->method('getBodyParams')
            ->willReturn(
                [
                    'payload' => [
                        'backref' => 'ShopModule::Order(id=55)',
                        'events' => [
                            659 => [
                                'id' => 659,
                                'event' => 'updated',
                                'details' => [
                                    'order' => [
                                        'paid_value_wt' => '156.0000',
                                        'paid_back_value_wt' => '156.0000'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'action' => 'events'
                ]
            );
        $this->productApiClientMock->method('setShopProductObjectSyncStatusForHook')
            ->willReturn(true);
        $this->webhook = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Api\Webhook\Webhook::class,
            [
                'request' => $this->requestMock,
                'authHelper' => $this->authHelper,
                'configHelper' => $this->configHelper,
                'json' => $this->json,
                'publisher' => $this->publisher,
                'jsonResponse' => $this->jsonResponse
            ]
        );
        $this->productsHelper = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Helper\Api\Products::class,
            [
                'orderApiClient' => $this->orderApiClientMock,
                'authHelper' => $this->authHelper,
                'productCollectionFactory' => $this->productCollectionFactory,
                'stockRegistry' => $this->stockRegistry,
                'productApiClient' => $this->productApiClientMock
            ]
        );
        $this->consumer = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Model\Consumer::class,
            [
                'ordersHelper' => $this->apiOrders,
                'orderApiClient' => $this->orderApiClientMock,
                'orderCollectionFactory' => $this->orderCollectionFactory,
                'productsHelper' => $this->productsHelper
            ]
        );

        $ex = new GeneralException('The new refund has not to be created', 0);
        $ex->setApiExceptionClass('ShopModule::GeneralException');
        $this->paymentApiClientMock->method('getNewWebPayment')->willThrowException($ex);
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customer.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     * @magentoConfigFixture current_store payment/storekeeper_payment_ideal/active 1
     */
    public function testPostExecute()
    {
        // Test Publisher
        $this->webhook->postExecuteWithResponse('1');
        $this->assertEquals(self::SUCCESS_JSON_RESPONSE_MESSAGE, $this->jsonResponse->getContent());

        // Test Consumer
        $orderId = $this->createTestOrderWithPayment(false);
        $order = $this->orderRepository->get($orderId);
        $invoice = $this->prepareInvoiceForOrder($order);
        $this->registerInvoiceAndSaveOrder($invoice, $order);
        $this->consumer->process(self::QUEUE_MESSAGE);

        $order = $this->orderRepository->get($orderId);
        $creditmemo = $order->getCreditmemosCollection()->getFirstItem();
        $this->assertEquals(Creditmemo::STATE_REFUNDED, $creditmemo->getState());

        $this->consumer->process(self::QUEUE_MESSAGE);
        $this->apiOrders->update($order, $order->getStorekeeperId());
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customer.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     */
    public function testSendProductInformation()
    {
        $this->consumer->process(self::QUEUE_MESSAGE_STOCK_CHANGE);
        $product = $this->getProductRepository()->getById('22');
        $this->assertEquals($product->getExtensionAttributes()->getStockItem()->getQty(), self::UPDATED_STOCK_ITEM_VALUE);
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Sales\Api\Data\InvoiceInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function prepareInvoiceForOrder(\Magento\Sales\Api\Data\OrderInterface $order): \Magento\Sales\Api\Data\InvoiceInterface
    {
        $orderService = Bootstrap::getObjectManager()->create(\Magento\Sales\Api\InvoiceManagementInterface::class);
        $invoice = $orderService->prepareInvoice($order);
        $invoice->register();

        return $invoice;
    }

    /**
     * @param \Magento\Sales\Api\Data\InvoiceInterface $invoice
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @throws \Exception
     */
    private function registerInvoiceAndSaveOrder(
        \Magento\Sales\Api\Data\InvoiceInterface $invoice,
        \Magento\Sales\Api\Data\OrderInterface $order
    ): void {
        $order->setIsInProcess(true);
        $order->setStorekeeperId(55);

        $transactionSave = Bootstrap::getObjectManager()->create(\Magento\Framework\DB\Transaction::class);
        $transactionSave->addObject($invoice)->addObject($order)->save();
    }

}
