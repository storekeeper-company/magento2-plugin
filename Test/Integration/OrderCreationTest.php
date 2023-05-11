<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\TestCase\AbstractController;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Customer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Model\Order\Address;

class OrderCreationTest extends TestCase
{
    const ORDER_INCREMENT_ID = '100001007';
    const STORE_KEEPER_ORDER_ID = 55;
    const STORE_KEEPER_ORDER_NUMBER = 'S08-' . self::ORDER_INCREMENT_ID;
    const PRODUCT_SKU = 'simple-2';
    protected $customerApiClientMock;
    protected $productApiClientMock;
    protected $orderApiClientMock;
    protected $ordersHelperMock;
    protected $apiOrders;
    protected $cronOrders;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->customerApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\CustomerApiClient::class);
        $this->productApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\ProductApiClient::class);
        $this->orderApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\OrderApiClient::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->orderRepository = Bootstrap::getObjectManager()->create(\Magento\Sales\Api\OrderRepositoryInterface::class);

        $this->customerApiClientMock->method('findCustomerRelationDataIdByEmail')
            ->willReturn(2);
        $this->productApiClientMock->method('getTaxRates')
            ->willReturn($this->getTaxRates());
        $this->orderApiClientMock->method('getNewOrderWithReturn')
            ->willReturn($this->getStoreKeeperOrder());

        $this->apiOrders = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Helper\Api\Orders::class,
            [
                'customerApiClient' => $this->customerApiClientMock,
                'productApiClient' => $this->productApiClientMock,
                'orderApiClient' => $this->orderApiClientMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'orderRepository' => $this->orderRepository
            ]
        );
        $this->cronOrders = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Cron\Orders::class,
            [
                'storeManager' => Bootstrap::getObjectManager()->create(\Magento\TestFramework\Store\StoreManager::class),
                'configHelper' => Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Helper\Config::class),
                'ordersHelper' => $this->apiOrders,
                'storeKeeperFailedSyncOrder' => Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\StoreKeeperFailedSyncOrderFactory::class)
            ]
        );
    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customer.php
     * @magentoConfigFixture current_store storekeeper_general/general/enabled 1
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_auth {"rights":"subuser","mode":"apikey","account":"centroitbv","subaccount":"64537ca6-18ae-41e5-a6a9-20b803f97117","user":"sync","apikey":"SE75vpzIky5Su6K0E5xQTuLBt2JoSYMd"}
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_sync_mode 4
     */
    public function testOrderCreation()
    {
        /** @var Customer $customer */
        $customer = $this->getCustomer();
        $billingAddress = $this->getBillingAddress();
        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');
        $product = $this->getProduct();
        $payment = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order\Payment::class);
        $payment->setMethod('checkmo');
        $customerIdFromFixture = 1;

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order\Item::class);
        $requestInfo = [
            'qty' => 1
        ];
        $orderItem->setProductId($product->getId())
            ->setTaxAmount(2.31)
            ->setTaxPercent(21)
            ->setQtyOrdered(1)
            ->setBasePrice($product->getPrice())
            ->setPrice($product->getPrice())
            ->setOriginalPrice(11)
            ->setPriceInclTax(13.31)
            ->setRowTotal($product->getPrice())
            ->setProductType($product->getTypeId())
            ->setName($product->getName())
            ->setSku($product->getSku())
            ->setStoreId(0)
            ->setProductId($product->getId())
            ->setSku($product->getSku())
            ->setProductOptions(['info_buyRequest' => $requestInfo]);

        /** @var \Magento\Sales\Model\Order $order */
        $order = Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class);
        $order->setIncrementId(self::ORDER_INCREMENT_ID);
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_NEW));
        $order->setCustomerIsGuest(false);
        $order->setCustomerId($customer->getId());
        $order->setCustomerEmail($customer->getEmail());
        $order->setCustomerFirstname($customer->getName());
        $order->setCustomerLastname($customer->getLastname());
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
        $order->setAddresses([$billingAddress, $shippingAddress]);
        $order->setPayment($payment);
        $order->addItem($orderItem);
        $order->setStoreId(Bootstrap::getObjectManager()->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId());
        $order->setSubtotal(100);
        $order->setBaseSubtotal(100);
        $order->setBaseGrandTotal(100);
        $order->setStoreCurrencyCode('USD');
        $order->setStorekeeperPaymentId(random_int(1, 100));

        $orderRepository = Bootstrap::getObjectManager()->create(OrderRepositoryInterface::class);
        $orderRepository->save($order);
        $this->assertEquals(1, $order->getStorekeeperOrderPendingSync());
        $this->assertEquals(\Magento\Sales\Model\Order::STATE_NEW, $order->getState());

        $this->cronOrders->execute();
    }

    /**
     * @return array
     */
    private function getAddresData(): array
    {
        return [
            'region' => 'CA',
            'region_id' => '12',
            'postcode' => '11111',
            'company' => 'Test Company',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'street' => 'street',
            'city' => 'Los Angeles',
            'email' => 'admin@example.com',
            'telephone' => '11111111',
            'country_id' => 'US'
        ];
    }

    /**
     * @return array
     */
    private function getTaxRates(): array
    {
        return [
            'data' =>
                [
                    [
                        'id' => 55,
                        'name' => 'Netherlands (standard)',
                        'alias' => 'standard',
                        'value' => 0.21,
                        'country_iso2' => 'NL',
                    ],
                    [
                        'id' => 56,
                        'name' => 'Netherlands (reduced: food / books / pharma / medical / hotels)',
                        'alias' => 'reduced_books_food_hotels_medical_pharma',
                        'value' => 0.06,
                        'country_iso2' => 'NL',
                    ],
                    [
                        'id' => 57,
                        'name' => 'Netherlands (reduced: food / books / pharma / medical / hotels - 2019)',
                        'alias' => 'reduced_2019_books_food_hotels_medical_pharma',
                        'value' => 0.09,
                        'country_iso2' => 'NL',
                    ],
                ],
            'total' => 3,
            'count' => 3,
        ];
    }

    /**
     * @return array
     */
    private function getStoreKeeperOrder(): array
    {
        return [
            'id' => self::STORE_KEEPER_ORDER_ID,
            'number' => self::STORE_KEEPER_ORDER_NUMBER
        ];
    }

    /**
     * @return Customer
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCustomer(): Customer
    {
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = Bootstrap::getObjectManager()->create(CustomerRegistry::class);

        return $customerRegistry->retrieve(1);
    }

    /**
     * @return ProductRepositoryInterface
     */
    private function getProductRepository(): ProductRepositoryInterface
    {
        return Bootstrap::getObjectManager()->create(ProductRepositoryInterface::class);
    }

    /**
     * @return ProductInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProduct(): ProductInterface
    {
        return $this->getProductRepository()->get(self::PRODUCT_SKU);
    }

    /**
     * @return Address
     */
    private function getBillingAddress(): Address
    {
        $billingAddress = Bootstrap::getObjectManager()->create(Address::class, ['data' => $this->getAddresData()]);
        $billingAddress->setAddressType('billing');

        return $billingAddress;
    }
}
