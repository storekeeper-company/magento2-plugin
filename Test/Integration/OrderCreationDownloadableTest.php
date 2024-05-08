<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\Sales\Model\Order;

/**
 * @magentoDbIsolation enabled
 */
class OrderCreationDownloadableTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/order_with_downloadable_product.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"REDACTED"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     * @magentoDbIsolation enabled
     */
    public function testOrderWithDownlaodableProduct()
    {
        $existingOrder = Bootstrap::getObjectManager()->create(Order::class)
            ->loadByIncrementId('100000001');
        $this->assertOrderCreation($existingOrder);
    }
}
