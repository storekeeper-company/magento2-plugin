<?php
namespace StoreKeeper\StoreKeeper\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    public const SYNC_NONE = 1;
    public const SYNC_PRODUCTS = 2;
    public const SYNC_ORDERS = 4;
    public const SYNC_ALL = 8;
    const STOREKEEPER_PAYMENT_METHODS_ACTIVE = 'storekeeper_payment_methods/payment_methods/enabled	';
    const STOREKEEPER_SYNC_MODE = 'storekeeper_general/general/storekeeper_sync_mode';
    const STOREKEEPER_TOKEN = 'storekeeper_general/general/storekeeper_token';
    const IS_DEBUG_LOGS = 'storekeeper_general/general/debug_logs';
    const STOREKEEPER_EXPORT_FEATURED_ATTRIBUTES_MAPPING_SECTION = 'storekeeper_export/featured_attributes_mapping';
    const STOREKEEPER_STOCK_SOURCE = 'storekeeper_general/general/storekeeper_stock_source';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    private function getScopeConfigValue(string $key, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->scopeConfig->getValue($key, $scope, $storeId);
    }

    public function getSyncAuth($storeId)
    {
        return $this->getScopeConfigValue('storekeeper_general/general/storekeeper_sync_auth', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getAuthEmail()
    {
        return $this->getScopeConfigValue('storekeeper_general/general/auth_email');
    }

    public function getAuthPassword()
    {
        return $this->getScopeConfigValue('storekeeper_general/general/auth_password');
    }

    public function isAvailable($storeId): bool
    {
        $active = $this->getScopeConfigValue(self::STOREKEEPER_PAYMENT_METHODS_ACTIVE, $storeId);

        if (!$active) {
            return false;
        }

        return true;
    }

    public function hasMode($storeId, $flags)
    {
        $mode = $this->getScopeConfigValue(self::STOREKEEPER_SYNC_MODE, ScopeInterface::SCOPE_STORE, $storeId);

        return ($mode & $flags) != 0;
    }

    public function getToken($storeId)
    {
        return $this->getScopeConfigValue(self::STOREKEEPER_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isDebugLogs($storeId)
    {
        return $this->getScopeConfigValue(self::IS_DEBUG_LOGS, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getFeaturedAttributesMapping()
    {
        return $this->getScopeConfigValue(self::STOREKEEPER_EXPORT_FEATURED_ATTRIBUTES_MAPPING_SECTION);
    }

    public function getStockSource()
    {
        return $this->getScopeConfigValue(self::STOREKEEPER_STOCK_SOURCE);
    }

    public function getLocaleCode($storeId)
    {
        return $this->getScopeConfigValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getShippingTaxCalculation($storeId)
    {
        return $this->getScopeConfigValue('tax/calculation/shipping_includes_tax', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isShippingCarrierActive($storeId)
    {
        return $this->getScopeConfigValue('carriers/storekeeper/active', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function isCatalogPricesIncludesTax($storeId)
    {
        return $this->getScopeConfigValue('tax/calculation/price_includes_tax', ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getDefaultCountry()
    {
        return $this->getScopeConfigValue('tax/defaults/country', ScopeInterface::SCOPE_STORE);
    }

    public function isProductImagesSyncActive($storeId)
    {
        return $this->getScopeConfigValue('storekeeper_import/import_data/sync_product_images', ScopeInterface::SCOPE_STORE, $storeId);
    }
}
