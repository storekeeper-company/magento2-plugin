<?php
namespace StoreKeeper\StoreKeeper\Helper;

use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STOREKEEPER_PAYMENT_METHODS_ACTIVE = 'storekeeper_payment_methods/payment_methods/enabled	';

    const STOREKEEPER_SYNC_MODE = 'storekeeper_general/general/storekeeper_sync_mode';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    private function getScopeConfigValue(string $key, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId = null)
    {
        return $this->scopeConfig->getValue($key, $scope, $storeId);
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

    public function getMode($storeId)
    {
        $mode = $this->getScopeConfigValue(self::STOREKEEPER_SYNC_MODE, ScopeInterface::SCOPE_STORE, $storeId);

        switch ($mode) {
            case 0:
            case 1:
                return 'order_only_mode';
            default:
                return 'default';
        }
    }
}
