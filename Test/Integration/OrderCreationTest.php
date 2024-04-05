<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Model\Customer;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @magentoDbIsolation enabled
 */
class OrderCreationTest extends AbstractTest
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customer.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     */
    public function testOrderCreation()
    {
        $customer = $this->getCustomer();
        $orderData = $this->getOrderData(self::SIMPLE_PRODUCT_SKU);
        $order = $this->createOrder(
            $orderData['billingAddress'],
            $orderData['shippingAddress'],
            $customer,
            $orderData['payment'],
            $orderData['orderItem']
        );
        $this->assertOrderCreation($order);
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/order_configurable_product.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     * @magentoDbIsolation enabled
     */
    public function testOrderWithConfigurableProduct()
    {
        $existingOrder = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');
        $this->assertOrderCreation($existingOrder);
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/order_configurable_product_w_special_price.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     * @magentoDbIsolation enabled
     */
    public function testOrderWithConfigurableProductWithSprcialPrice()
    {
        $existingOrder = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class)
            ->loadByIncrementId('100000001');
        $this->assertOrderCreation($existingOrder);
    }
}
