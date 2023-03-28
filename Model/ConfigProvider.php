<?php

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\ApiWrapper\ModuleApiWrapper;

class ConfigProvider implements ConfigProviderInterface
{
    const STOREKEEPER_SHOP_MODULE_NAME = 'ShopModule';

    private Auth $authHelper;

    /**
     * ConfigProvider constructor.
     * @param Auth $authHelper
     */
    public function __construct(
        Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        $config = [];
        $config['storekeeper_payment_methods'] = $this->getPaymentMethods();

        return $config;
    }

    /**
     * @return array
     */
    private function getPaymentMethods(): array
    {
        $storeKeeperPaymentMethods =  $this->getShopModule()->listTranslatedPaymentMethodForHooks('0', 0, 10, null, []);
        foreach ($storeKeeperPaymentMethods['data'] as $storeKeeperPaymentMethod) {
            $paymentMethods[$storeKeeperPaymentMethod['id']] = [
                'payment_method_title' => $storeKeeperPaymentMethod['title'],
                'payment_method_logo_url' => $storeKeeperPaymentMethod['image_url']
            ];
        }

        return $paymentMethods;
    }

    /**
     * @return ModuleApiWrapper
     */
    private function getShopModule(): ModuleApiWrapper
    {
        return $this->authHelper->getModule(self::STOREKEEPER_SHOP_MODULE_NAME, $this->getStoreId());
    }

    /**
     * @return string
     */
    private function getStoreId(): string
    {
        return $this->authHelper->storeManager->getStore()->getId();
    }
}
