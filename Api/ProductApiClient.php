<?php

namespace StoreKeeper\StoreKeeper\Api;

use StoreKeeper\StoreKeeper\Api\ApiClient;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;

class ProductApiClient extends ApiClient
{
    private const STOREKEEPER_PRODUCTS_MODULE_NAME = 'ProductsModule';

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    public function getProductModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->getModule(self::STOREKEEPER_PRODUCTS_MODULE_NAME, $storeId);
    }
}
