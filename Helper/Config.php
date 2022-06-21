<?php
namespace StoreKeeper\StoreKeeper\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const STOREKEEPER_PAYMENT_METHODS_ACTIVE = 'storekeeper_payment_methods/payment_methods/enabled	';

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        // $this->stockItemRepository = $stockItemRepository;
        // $this->stockRegistryInterface = $stockRegistryInterface;
    }

    private function getScopeConfigValue(string $key, $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($key, $scope);
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

}
