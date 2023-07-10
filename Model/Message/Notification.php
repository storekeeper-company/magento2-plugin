<?php
namespace StoreKeeper\StoreKeeper\Model\Message;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Directory\Model\Config\Source\Country;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Framework\Phrase;

class Notification implements \Magento\Framework\Notification\MessageInterface
{
    const XML_PATH_GENERAL_COUNTRY_DEFAULT = 'general/country/default';
    const XML_PATH_EU_COUNTRIES_LIST = 'general/country/eu_countries';
    const STOREKEEPER_REQUIRED_TAX_CALCULATION_CONFIGS = [
        'algorithm' => 'TOTAL_BASE_CALCULATION', // Tax Calculation Method Based On
        'based_on' => 'shipping', // Tax Calculation Based On
        'price_includes_tax' => true, // Catalog Prices
        'shipping_includes_tax' => true, // Shipping Prices
        'apply_after_discount' => true, // Apply Customer Tax
        'discount_tax' => true, // Apply Discount On Prices
        'apply_tax_on' => '0' // Apply Tax On: '0' - Custom price if available
    ];

    private ScopeConfigInterface $scopeConfig;
    private Country $country;
    private TaxConfig $taxConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Country $country,
        TaxConfig $taxConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->country = $country;
        $this->taxConfig = $taxConfig;
    }

    /**
     * @return string
     */
    public function getIdentity(): string
    {
        return 'sk_tax_notification';
    }

    /**
     * @return bool
     */
    public function isDisplayed(): bool
    {
        $result = false;
        if (in_array($this->getDefaultCountry(), $this->getEuCountries()) && !$this->areTaxCalculationConfigsEqual()) {
            $result = true;
        }

        return $result;
    }

    /**
     * @return Phrase
     */
    public function getText(): Phrase
    {
        $url = 'https://experienceleague.adobe.com/docs/commerce-admin/stores-sales/site-store/taxes/international-tax-guidelines.html#step-5%3A-configure-tax-settings-for-france';

        return __('Please adjust your Tax Configs according to <a href="%1">Tax guidelines by country</a>.',$url        );
    }

    /**
     * @return int
     */
    public function getSeverity(): int
    {
        return self::SEVERITY_CRITICAL;
    }

    /**
     * @return string
     */
    private function getDefaultCountry(): string
    {
        return$this->scopeConfig->getValue(self::XML_PATH_GENERAL_COUNTRY_DEFAULT);
    }

    /**
     * @return array
     */
    private function getEuCountries(): array
    {
        return explode(',', $this->scopeConfig->getValue(self::XML_PATH_EU_COUNTRIES_LIST));
    }

    /**
     * @return array
     */
    private function getTaxCalculationConfigs(): array
    {
        return [
'algorithm' => $this->taxConfig->getAlgorithm(),
'based_on' => $this->scopeConfig->getValue(TaxConfig::CONFIG_XML_PATH_BASED_ON),
'price_includes_tax' => $this->taxConfig->priceIncludesTax(),
'shipping_includes_tax' => $this->taxConfig->shippingPriceIncludesTax(),
'apply_after_discount' => $this->taxConfig->applyTaxAfterDiscount(),
'discount_tax' => $this->taxConfig->discountTax(),
            'apply_tax_on' => $this->scopeConfig->getValue(TaxConfig::CONFIG_XML_PATH_APPLY_ON)
        ];
    }

    /**
     * @return bool
     */
    private function areTaxCalculationConfigsEqual(): bool
    {
        return empty(array_diff($this->getTaxCalculationConfigs(), self::STOREKEEPER_REQUIRED_TAX_CALCULATION_CONFIGS));
    }
}
