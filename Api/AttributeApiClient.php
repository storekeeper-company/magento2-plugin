<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;
use StoreKeeper\StoreKeeper\Logger\Logger;

/**
 * ApiWrapper for SK ShopModule which will provide access to attribute type and options in future
 */
class AttributeApiClient extends ApiClient
{
    const STOREKEEPER_SHOP_MODULE_NAME = 'ShopModule';

    /**
     * Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Logger $logger
    ) {
        parent::__construct($scopeConfig, $logger);
    }

    /**
     * @param string $storeId
     * @param int $attributeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    public function getListAttributeOptions(string $storeId, int $attributeId): ModuleApiWrapperInterface
    {
        return $this->getShopModule($storeId)->listAttributeOptions(0, 999, null, [[
            'name' => 'attribute/id__=',
            'val' => $attributeId
        ]]);
    }

    /**
     * @param string $storeId
     * @param int $attributeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    public function getAttributeById(string $storeId, int $attributeId): ModuleApiWrapperInterface
    {
        return $this->getShopModule($storeId)->listAttributes(0, 20, null, [[
            'name' => 'id__=',
            'val' => $attributeId
        ]]);
    }

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    private function getShopModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->getModule(self::STOREKEEPER_SHOP_MODULE_NAME, $storeId);
    }
}
