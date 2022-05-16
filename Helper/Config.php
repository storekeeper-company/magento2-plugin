<?php
namespace StoreKeeper\StoreKeeper\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
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
        return $this->getScopeConfigValue('storekeeper-general/general/auth_email');
    }

    public function getAuthPassword()
    {
        return $this->getScopeConfigValue('storekeeper-general/general/auth_password');
    }

    // public function getScopeConfigValue(string $key)
    // {
    //     return $this->scopeConfig->getValue($key, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    // }
}