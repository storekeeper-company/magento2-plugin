<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\Error;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Shipping;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\StoreKeeper\Model\Carrier\Storekeeper as StorekeeperCarrier;

abstract class CarrierTestAbstract extends \PHPUnit\Framework\TestCase
{
    protected $carrier = 'storekeeper';
    protected $storekeeperCarrier;
    protected $orderApiClientMock;
    protected $scopeConfig;
    protected $configHelper;
    protected $storeManager;
    protected $_rateResultFactory;
    protected $_rateMethodFactory;
    protected $shipping;
    protected $logger;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->orderApiClientMock = $this->createMock(OrderApiClient::class);
        $this->scopeConfig = Bootstrap::getObjectManager()->create(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $this->configHelper = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Helper\Config::class);
        $this->storeManager = Bootstrap::getObjectManager()->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->_rateResultFactory = Bootstrap::getObjectManager()->create(\Magento\Shipping\Model\Rate\ResultFactory::class);
        $this->_rateMethodFactory = Bootstrap::getObjectManager()->create(\Magento\Quote\Model\Quote\Address\RateResult\MethodFactory::class);
        $this->logger = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Logger\Logger::class);
        $this->shipping = Bootstrap::getObjectManager()->create(Shipping::class);

        $this->orderApiClientMock->method('getListShippingMethodsForHooks')->willReturn($this->getApiRates());

        $this->storekeeperCarrier = $objectManager->getObject(StorekeeperCarrier::class, [
            'orderApiClient' => $this->orderApiClientMock,
            'scopeConfig' => $this->scopeConfig,
            'configHelper' => $this->configHelper,
            'storeManager' => $this->storeManager,
            '_rateResultFactory' => $this->_rateResultFactory,
            '_rateMethodFactory' => $this->_rateMethodFactory,
            'logger' => $this->logger
        ]);
    }

    /**
     * Test that carrier available and applicable
     *
     * @return void
     */
    public function testCollectRatesWhenShippingCarrierIsAvailableAndApplicable()
    {
        $request = $this->createRequest();
        $expectedRates = $this->getExpectedRates();

        $actualRates = $this->storekeeperCarrier->collectRates($request)->getAllRates();

        //Assert equal amount of expected and acual collected rates
        self::assertEquals(count($expectedRates), count($actualRates));
        foreach ($actualRates as $i => $actualRate) {
            $actualRate = $actualRate->getData();
            //Assert carrier code, title, method, method title, cost and price
            self::assertEquals($expectedRates[$i], $actualRate);
        }
    }

    /**
     * Test that carrier available and not applicable
     *
     * @return void
     */
    public function testCollectRatesWhenShippingCarrierIsNotAvailableAndNotApplicable()
    {
        $request = $this->createRequest();
        $result = $this->shipping->collectRatesByAddress($request, $this->carrier);
        $rate = $this->getRate($result->getResult());
        //Assert empty rate due to deactivated state of carrier
        static::assertNull($rate);
    }

    /**
     * Test that carrier available and country is not in a list
     *
     * @return void
     */
    public function testCollectRatesCountryNotValid()
    {
        $request = $this->createRequest();
        $request->setDestCountryId('US');
        $actualRates = $this->storekeeperCarrier->collectRates($request)->getAllRates();
        //Assert empty list of rates due to invalid desctionation country
        static::assertEmpty($actualRates);
    }

    /**
     * Test free shipping when order total > 'free_from_value_wt'
     *
     * @return void
     */
    public function testCollectRatesFreeShipping()
    {
        $request = $this->createRequest();
        $request->setBaseSubtotalInclTax(205);
        $actualRates = $this->storekeeperCarrier->collectRates($request)->getAllRates();
        foreach ($actualRates as $i => $actualRate) {
            //Assert carrier 0 cost and price
            self::assertEquals(0, $actualRate->getPrice());
            self::assertEquals(0, $actualRate->getCost());
        }
    }

    /**
     * Test exception handling during process of collect rates
     *
     * @return void
     */
    public function testCollectRatesApiExceptionHandling()
    {
        $ex = new \Exception('Api connection error', 0);
        $this->orderApiClientMock->method('getListShippingMethodsForHooks')->willThrowException($ex);
        $request = $this->createRequest();
        $request->setBaseSubtotalInclTax(205);
        $actualRates = $this->storekeeperCarrier->collectRates($request)->getAllRates();
        //Assert empty list of rates due to exception during collectRates
        static::assertEmpty($actualRates);
    }

    /**
     * Create Rate Request
     *
     * @return RateRequest
     */
    private function createRequest(): RateRequest
    {
        $requestData = $this->getRequestData();

        return Bootstrap::getObjectManager()->create(RateRequest::class, ['data' => $requestData]);
    }

    /**
     * Returns shipping rate by the result.
     *
     * @param Result $result
     * @return Method|Error
     */
    private function getRate(Result $result)
    {
        $rates = $result->getAllRates();

        return array_pop($rates);
    }

    /**
     * Returns request data.
     *
     * @return array
     */
    private function getRequestData(): array
    {
        return [
            'dest_country_id' => 'DE',
            'dest_region_id' => '82',
            'store_id' => 1,
            'website_id' => '1',
            'base_subtotal_incl_tax' => 5
        ];
    }

    /**
     * SK Api rates mock
     * @return array
     */
    private function getApiRates(): array
    {
        return [
            'data' => [
                0 => [
                    'shipping_type' => [
                        'alias' => 'PickupAtStore'
                    ],
                    'shipping_method_price_flat_strategy' => [
                        'currency_iso3' => 'EUR',
                        'ppu' => 0,
                        'ppu_wt' => 0
                    ],
                    'name' => 'Afhalen',
                    'enabled' => true,
                    'country_iso2s' => ['NL', 'DE']
                ],
                1 => [
                    'shipping_type' => [
                        'alias' => 'Parcel'
                    ],
                    'shipping_method_price_flat_strategy' => [
                        'currency_iso3' => 'EUR',
                        'ppu' => 4.13,
                        'ppu_wt' => 5,
                        'free_from_value_wt' => 200
                    ],
                    'name' => 'Pakket',
                    'enabled' => true,
                    'country_iso2s' => ['NL', 'DE']
                ]
            ],
            'total' => 2,
            'count' => 2
        ];
    }

    /**
     * Expected rates array
     * @return array[]
     */
    private function getExpectedRates(): array
    {
        return [
            ['carrier' => 'storekeeper', 'carrier_title' => 'Afhalen', 'cost' => 0, 'method' => 'PickupAtStore', 'method_title' => __('PickupAtStore'), 'price' => 0],
            ['carrier' => 'storekeeper', 'carrier_title' => 'Pakket', 'cost' => 5, 'method' => 'Parcel', 'method_title' => __('Parcel'), 'price' => 5]
        ];
    }
}
