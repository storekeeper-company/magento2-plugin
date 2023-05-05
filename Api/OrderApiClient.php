<?php

namespace StoreKeeper\StoreKeeper\Api;

use StoreKeeper\StoreKeeper\Api\ApiClient;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;

class OrderApiClient extends ApiClient
{
    private const STOREKEEPER_SHOP_MODULE_NAME = 'ShopModule';
    private const STOREKEEPER_PAYMENT_MODULE_NAME = 'PaymentModule';

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    public function getShopModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->getModule(self::STOREKEEPER_SHOP_MODULE_NAME, $storeId);
    }

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    public function getPaymentModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->getModule(self::STOREKEEPER_PAYMENT_MODULE_NAME, $storeId);
    }
}
