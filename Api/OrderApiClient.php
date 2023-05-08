<?php

namespace StoreKeeper\StoreKeeper\Api;

use StoreKeeper\StoreKeeper\Api\ApiClient;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;

class OrderApiClient extends ApiClient
{
    private const STOREKEEPER_SHOP_MODULE_NAME = 'ShopModule';

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

    /**
     * @param string $storeId
     * @param string $storeKeeperId
     * @param array $refundPayments
     * @throws \Exception
     */
    public function refundAllOrderItems(string $storeId, string $storeKeeperId, array $refundPayments): void
    {
        $this->getShopModule($storeId)->refundAllOrderItems(['id' => $storeKeeperId, 'refund_payments' => $refundPayments]);
    }

    /**
     * @param string $storeId
     * @param string $storekeeperId
     * @return array
     * @throws \Exception
     */
    public function getStoreKeeperOrder(string $storeId, string $storekeeperId): array
    {
        return $this->getShopModule($storeId)->getOrder($storekeeperId);
    }

    /**
     * @param string $storeId
     * @param string $storeKeeperId
     * @return string|null
     * @throws \Exception
     */
    public function getOrderStatusPageUrl(string $storeId, string $storeKeeperId): ?string
    {
        return $this->getShopModule($storeId)->getOrderStatusPageUrl($storeKeeperId);
    }
}
