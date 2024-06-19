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
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\StoreKeeper\Model\Carrier\Storekeeper as StorekeeperCarrier;

class CarrierTest extends CarrierTestAbstract
{
    /**
     * @magentoConfigFixture default_store carriers/storekeeper/active 1
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store tax/calculation/shipping_includes_tax 1
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function testCollectRatesWhenShippingCarrierIsAvailableAndApplicable()
    {
        parent::testCollectRatesWhenShippingCarrierIsAvailableAndApplicable();
    }

    /**
     * @magentoConfigFixture default_store carriers/storekeeper/active 1
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store tax/calculation/shipping_includes_tax 1
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function testCollectRatesFreeShipping()
    {
        parent::testCollectRatesFreeShipping();
    }

    /**
     * @magentoConfigFixture default_store carriers/storekeeper/active 1
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store tax/calculation/shipping_includes_tax 1
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function testCollectRatesCountryNotValid()
    {
        parent::testCollectRatesCountryNotValid();
    }

    /**
     * @magentoConfigFixture default_store carriers/storekeeper/active 1
     * @magentoConfigFixture default/currency/options/base EUR
     * @magentoConfigFixture default_store tax/calculation/shipping_includes_tax 1
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function testCollectRatesApiExceptionHandling()
    {
        parent::testCollectRatesApiExceptionHandling();
    }

    /**
     * @magentoConfigFixture default_store carriers/storekeeper/active 0
     */
    // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod
    public function testCollectRatesWhenShippingCarrierIsNotAvailableAndNotApplicable()
    {
        parent::testCollectRatesWhenShippingCarrierIsNotAvailableAndNotApplicable();
    }
}
