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
     * @return array|null
     * @throws \Exception
     */
    public function getAttributeById(string $storeId, int $attributeId): ?array
    {
        $attributes = $this->getShopModule($storeId)->listAttributesForHook(0, 1, null, [[
            'name' => 'id__=',
            'val' => $attributeId
        ]]);

        return $attributes['data'][0] ?? null;
    }

    /**
     * @param string $storeId
     * @param array $attributeIds
     * @return array|null
     * @throws \Exception
     */
    public function getAttributesByIds(string $storeId, array $attributeIds): ?array
    {
        $attributes = $this->getShopModule($storeId)->listAttributesForHook(0, 999, null, [[
            'name' => 'id__in_list',
            'multi_val' => $attributeIds
        ]]);

        return $attributes['data'] ?? null;
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
