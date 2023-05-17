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
use Magento\Sales\Model\Order;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddressInterface;
use Magento\Tax\Model\ClassModel as TaxClassModel;
use Magento\Tax\Model\Calculation\Rate as TaxRateCalculation;
use Magento\Tax\Model\Calculation\Rule as TaxRuleCalculation;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Checkout\Api\Data\TotalsInformationInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Checkout\Model\PaymentInformationManagement;

class OrderCreationTest extends TestCase
{
    const ORDER_INCREMENT_ID = '100001007';
    const STORE_KEEPER_ORDER_ID = 55;
    const STORE_KEEPER_ORDER_NUMBER = 'S08-' . self::ORDER_INCREMENT_ID;
    const PRODUCT_SKU = 'simple-2';

    protected $customerApiClientMock;
    protected $productApiClientMock;
    protected $orderApiClientMock;
    protected $requestMock;
    protected $paymentApiClientMock;

    protected $searchCriteriaBuilder;
    protected $orderRepository;
    protected $customerSession;
    protected $cartManagement;
    protected $cartItemRepository;
    protected $shippingAddressManagement;
    protected $totalsInformationManagement;
    protected $paymentMethodManagement;
    protected $cartRepository;
    protected $checkoutSession;
    protected $persistentSessionHelper;
    protected $url;
    protected $quoteRepository;
    protected $response;
    protected $resultRedirectFactory;
    protected $invoice;
    protected $apiOrders;
    protected $cronOrders;
    protected $redirect;
    protected $finish;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->customerApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\CustomerApiClient::class);
        $this->productApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\ProductApiClient::class);
        $this->orderApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\OrderApiClient::class);
        $this->requestMock = $this->createMock(\Magento\TestFramework\Request::class);
        $this->paymentApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\PaymentApiClient::class);

        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->orderRepository = Bootstrap::getObjectManager()->create(\Magento\Sales\Api\OrderRepositoryInterface::class);
        $this->customerSession = Bootstrap::getObjectManager()->create(\Magento\Customer\Model\Session::class);
        $this->cartManagement = Bootstrap::getObjectManager()->create(\Magento\Quote\Api\CartManagementInterface::class);
        $this->cartItemRepository = Bootstrap::getObjectManager()->create(\Magento\Quote\Api\CartItemRepositoryInterface::class);
        $this->shippingAddressManagement = Bootstrap::getObjectManager()->create(\Magento\Quote\Model\ShippingAddressManagementInterface::class);
        $this->totalsInformationManagement = Bootstrap::getObjectManager()->create(\Magento\Checkout\Api\TotalsInformationManagementInterface::class);
        $this->paymentMethodManagement = Bootstrap::getObjectManager()->create(\Magento\Quote\Api\PaymentMethodManagementInterface::class);
        $this->cartRepository = Bootstrap::getObjectManager()->create(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->checkoutSession = Bootstrap::getObjectManager()->create(\Magento\Checkout\Model\Session::class);
        $this->persistentSessionHelper = Bootstrap::getObjectManager()->create(\Magento\Persistent\Helper\Session::class);
        $this->url = Bootstrap::getObjectManager()->create(\Magento\Framework\UrlInterface::class);
        $this->quoteRepository = Bootstrap::getObjectManager()->create(\Magento\Quote\Model\QuoteRepository::class);
        $this->response = Bootstrap::getObjectManager()->create(\Magento\Framework\App\ResponseInterface::class);
        $this->resultRedirectFactory = Bootstrap::getObjectManager()->create(\Magento\Framework\Controller\Result\RedirectFactory::class);
        $this->invoice = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Invoice::class);

        $this->customerApiClientMock->method('findCustomerRelationDataIdByEmail')
            ->willReturn(2);
        $this->productApiClientMock->method('getTaxRates')
            ->willReturn($this->getTaxRates());
        $this->orderApiClientMock->method('getNewOrderWithReturn')
            ->willReturn($this->getStoreKeeperOrder());
        $this->requestMock->method('getParam')
            ->willReturnCallback(
                function ($key) {
                    if ($key == 'storekeeper_payment_method_id') {
                        return '1';
                    }
                }
            );
        $this->requestMock->method('getParams')
            ->willReturn([
                'orderID' => '1'
            ]);
        $this->paymentApiClientMock->method('getStoreKeeperPayment')
            ->willReturn([
                'id' => 1,
                'payment_url' => 'https://storekeepercloud.com/'
            ]);
        $this->paymentApiClientMock->method('syncWebShopPaymentWithReturn')
            ->willReturn([
                'status' => 'paid'
            ]);

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
        $this->redirect = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Controller\Checkout\Redirect::class,
            [
                'request' => $this->requestMock,
                'checkoutSession' => $this->checkoutSession,
                'ordersHelper' => $this->apiOrders,
                '_url' => $this->url,
                'paymentApiClient' => $this->paymentApiClientMock,
                'quoteRepository' => $this->quoteRepository,
                '_response' => $this->response
            ]
        );
        $this->finish = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Controller\Checkout\Finish::class,
            [
                'resultRedirectFactory' => $this->resultRedirectFactory,
                'request' => $this->requestMock,
                'orderRepository' => $this->orderRepository,
                'paymentApiClient' => $this->paymentApiClientMock,
                'invoice' => $this->invoice,
                'checkoutSession' => $this->checkoutSession,
            ]
        );
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
        $savedOrder = $this->orderRepository->get('1');

        $this->assertEquals(self::STORE_KEEPER_ORDER_ID, $savedOrder->getStorekeeperId());
        $this->assertEquals(self::STORE_KEEPER_ORDER_NUMBER, $savedOrder->getStorekeeperOrderNumber());

    }

    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customer.php
     * @magentoConfigFixture current_store payment/storekeeper_payment_ideal/active 1
     */
    public function testPayment()
    {
        //Retrieve customer
        $customer = $this->getCustomer();
        $this->customerSession->loginById($customer->getId());

        //Retrieve product from repository
        $product = $this->getProduct();
        $product->setOptions(null);
        $taxClassId = $this->getTaxClass()->getId();
        $this->createTaxRule($this->getTaxRate(), $taxClassId);
        $product->setTaxClassId($taxClassId);
        $this->getProductRepository()->save($product);

        //Add item to newly created customer cart
        $cartId = $this->cartManagement->createEmptyCartForCustomer($customer->getId());
        $quoteItem = Bootstrap::getObjectManager()->create(CartItemInterface::class);
        $quoteItem->setQuoteId($cartId);
        $quoteItem->setProduct($product);
        $quoteItem->setQty(2);
        $this->cartItemRepository->save($quoteItem);

        //Fill out address data
        $billingAddress = Bootstrap::getObjectManager()->create(
            QuoteAddressInterface::class,
            [
                'data' => $this->getAddresData()
            ]
        );
        $shippingAddress = clone $billingAddress;
        $shippingAddress->setSameAsBilling(true);
        $this->shippingAddressManagement->assign($cartId, $shippingAddress);
        $shippingAddress = $this->shippingAddressManagement->get($cartId);

        //Determine shipping options and collect totals
        $totals = Bootstrap::getObjectManager()->create(TotalsInformationInterface::class);
        $totals->setAddress($shippingAddress);
        $totals->setShippingCarrierCode('flatrate');
        $totals->setShippingMethodCode('flatrate');
        $this->totalsInformationManagement->calculate($cartId, $totals);

        //Select payment method
        $payment = Bootstrap::getObjectManager()->create(PaymentInterface::class);
        $payment->setMethod('storekeeper_payment_ideal');
        $this->paymentMethodManagement->set($cartId, $payment);
        $quote = $this->cartRepository->get($cartId);

        //Verify checkout session contains correct quote data
        $this->checkoutSession->clearQuote();
        $this->checkoutSession->setQuoteId($quote->getId());

        //Set up persistent session data and expire customer session
        $this->persistentSessionHelper->getSession()->setCustomerId($customer->getId())
            ->setPersistentCookie(10000, '');
        $this->persistentSessionHelper->getSession()->removePersistentCookie()->setPersistentCookie(100000000, '');
        $this->customerSession->setIsCustomerEmulated(true)->expireSessionCookie();

        //Submit order as expired/emulated customer
        //Grab created order data
        $paymentManagement = Bootstrap::getObjectManager()->create(
            PaymentInformationManagement::class
        );
        $orderId = $paymentManagement->savePaymentInformationAndPlaceOrder(
            $this->checkoutSession->getQuote()->getId(),
            $quote->getPayment(),
            $billingAddress
        );
        $order = $this->orderRepository->get($orderId);

        //Execute Redirect and Finish controllers
        $this->redirect->execute();
        $order = $this->orderRepository->get($orderId);
        $this->finish->execute();

        //Assert order state to 'processing' state
        $order = $this->orderRepository->get($orderId);
        $this->assertEquals($order->getState(), Order::STATE_PROCESSING);
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
            'country_id' => 'NL'
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

    /**
     * @return QuoteAddressInterface
     */
    private function getQuoteBillingAddress(): QuoteAddressInterface
    {
        $quoteBillingAddress = Bootstrap::getObjectManager()->create(QuoteAddressInterface::class, ['data' => $this->getAddresData()]);
        $quoteBillingAddress->setAddressType('billing');

        return $quoteBillingAddress;
    }

    /**
     * @return TaxClassModel
     * @throws \Exception
     */
    private function getTaxClass(): TaxClassModel
    {
        $taxClassName = 'Test Tax Class';
        $taxClassType = TaxClassModel::TAX_CLASS_TYPE_PRODUCT;
        $taxClass = Bootstrap::getObjectManager()->create(TaxClassModel::class);
        $taxClass->setClassName($taxClassName);
        $taxClass->setClassType($taxClassType);
        $taxClass->save();

        return $taxClass;
    }

    /**
     * @return TaxRateCalculation
     * @throws \Exception
     */
    private function getTaxRate(): TaxRateCalculation
    {
        $taxRateData = [
            'tax_country_id' => 'NL',
            'tax_region_id' => '0',
            'tax_postcode' => '*',
            'code' => 'Test Tax Rate',
            'rate' => '21.0000',
        ];
        $taxRate = Bootstrap::getObjectManager()->create(TaxRateCalculation::class);
        $taxRate->setData($taxRateData);
        $taxRate->save();

        return $taxRate;
    }

    /**
     * @param TaxRateCalculation $taxRate
     * @param string $taxClassId
     * @throws \Exception
     */
    private function createTaxRule(TaxRateCalculation $taxRate, string $taxClassId): void
    {
        $taxRuleData = [
            'code' => 'Test Tax Rule',
            'tax_rate_ids' => [$taxRate->getId()],
            'customer_tax_class_ids' => [3], // Customer tax class IDs if needed
            'product_tax_class_ids' => [$taxClassId], // Product tax class IDs if needed
            'priority' => 0
        ];
        $taxRule = Bootstrap::getObjectManager()->create(TaxRuleCalculation::class);
        $taxRule->setData($taxRuleData);
        $taxRule->save();
    }
}
