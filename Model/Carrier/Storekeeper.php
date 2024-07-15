<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\StoreKeeper\Helper\Config as ConfigHelper;
use StoreKeeper\StoreKeeper\Logger\Logger;

class Storekeeper extends AbstractCarrier implements CarrierInterface
{
    protected $_code = 'storekeeper';
    protected $_isFixed = true;
    protected $_rateResultFactory;
    protected $_rateMethodFactory;
    protected OrderApiClient $orderApiClient;
    protected ConfigHelper $configHelper;
    protected StoreManagerInterface $storeManager;
    protected Logger $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param OrderApiClient $orderApiClient
     * @param ConfigHelper $configHelper
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        OrderApiClient $orderApiClient,
        ConfigHelper $configHelper,
        StoreManagerInterface $storeManager,
        Logger $skLogger,
        array $data = []
    ) {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->orderApiClient = $orderApiClient;
        $this->configHelper = $configHelper;
        $this->storeManager = $storeManager;
        $this->logger = $skLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->configHelper->isShippingCarrierActive($request->getStoreId())) {
            return false;
        }

        $result = $this->_rateResultFactory->create();

        try {
            $apiRates = $this->orderApiClient->getListShippingMethodsForHooks($request->getStoreId());

            if (array_key_exists('data', $apiRates) && array_key_exists('count', $apiRates)) {
                if ($apiRates['count'] > 0) {
                    $orderTotal = $request->getBaseSubtotalInclTax();
                    $storeId = $request->getStoreId();
                    $storeCurrency = $this->storeManager->getStore()->getBaseCurrencyCode();
                    foreach ($apiRates['data'] as $apiRate) {
                        if ($apiRate['enabled'] === true) {
                            if (!$this->validateCountry($apiRate, $request->getDestCountryId())) {
                                continue;
                            }
                            $apiRateCurrency = $apiRate['shipping_method_price_flat_strategy']['currency_iso3'];
                            if ($apiRateCurrency === $storeCurrency) {
                                $method = $this->_rateMethodFactory->create();
                                $carrierCode = $apiRate['shipping_type']['alias'];
                                $shippingPrice = $this->getShippingPrice($storeId, $apiRate, $orderTotal);

                                $method->setCarrier($this->_code);
                                $method->setCarrierTitle($apiRate['name']);
                                $method->setMethod($carrierCode);
                                $method->setMethodTitle(__($carrierCode));
                                $method->setPrice($shippingPrice);
                                $method->setCost($shippingPrice);

                                $result->append($method);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $this->logger->buildReportData($e));
            return $result;
        }

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => 'StoreKeeper'];
    }

    /**
     * @param int $storeId
     * @param array $apiRate
     * @param $orderTotal
     * @return int|mixed
     */
    private function getShippingPrice(int $storeId, array $apiRate, $orderTotal)
    {
        if (array_key_exists('free_from_value_wt', $apiRate['shipping_method_price_flat_strategy'])
            && $orderTotal >= $apiRate['shipping_method_price_flat_strategy']['free_from_value_wt']) {
            $shippingPrice = 0;
        } else {
            /**
             * 0 - values set here excludes tax
             * 1 - includes tax
             * Based on Magento\Tax\Model\System\Config\Source\PriceType
             * No core const available
             */
            if ($this->configHelper->getShippingTaxCalculation($storeId) == '0') {
                $shippingPrice = $apiRate['shipping_method_price_flat_strategy']['ppu'];
            } elseif ($this->configHelper->getShippingTaxCalculation($storeId) == '1') {
                $shippingPrice = $apiRate['shipping_method_price_flat_strategy']['ppu_wt'];
            }
        }

        return $shippingPrice;
    }

    /**
     * @param array $apiRate
     * @return bool
     */
    private function validateCountry(array $apiRate, string $countryCode): bool
    {
        $valid = true;
        if (array_key_exists('country_iso2s', $apiRate)) {
            if (array_search($countryCode, $apiRate['country_iso2s']) === false) {
                $valid = false;
            }
        }

        return $valid;
    }
}
