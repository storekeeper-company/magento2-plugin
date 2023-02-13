<?php
namespace StoreKeeper\StoreKeeper\Helper;

use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    public const SYNC_NONE = 1;
    public const SYNC_PRODUCTS = 2;
    public const SYNC_ORDERS = 4;
    public const SYNC_ALL = 8;

    const STOREKEEPER_PAYMENT_METHODS_ACTIVE = 'storekeeper_payment_methods/payment_methods/enabled	';

    const STOREKEEPER_SYNC_MODE = 'storekeeper_general/general/storekeeper_sync_mode';

    const STOREKEEPER_TOKEN = 'storekeeper_general/general/storekeeper_token';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    private function getScopeConfigValue(string $key, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->scopeConfig->getValue($key, $scope, $storeId);
    }

    public function getSyncAuth($storeId) {
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

    public function getToken($storeId): mixed {
        return $this->getScopeConfigValue(self::STOREKEEPER_TOKEN, ScopeInterface::SCOPE_STORE, $storeId);
    }
}