<?php

namespace StoreKeeper\StoreKeeper\Api;

use StoreKeeper\StoreKeeper\Api\ApiClient;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\ApiWrapper\ModuleApiWrapperInterface;

class PaymentApiClient extends ApiClient
{
    private const STOREKEEPER_PAYMENT_MODULE_NAME = 'PaymentModule';

    private OrderApiClient $orderApiClient;

    /**
     * PaymentApiClient constructor.
     * @param OrderApiClient $orderApiClient
     */
    public function __construct(
        OrderApiClient $orderApiClient
    ) {
        $this->orderApiClient = $orderApiClient;
    }

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    private function getPaymentModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->getModule(self::STOREKEEPER_PAYMENT_MODULE_NAME, $storeId);
    }

    /**
     * @param string $storeId
     * @param array $parameters
     * @return int
     * @throws \Exception
     */
    public function getNewWebPayment(string $storeId, array $parameters = []): int
    {
        return $this->getPaymentModule($storeId)->newWebPayment($parameters);
    }

    /**
     * @param string $storeId
     * @param string $storeKeeperId
     * @param array $paymentIds
     * @retrun void
     */
    public function attachPaymentIdsToOrder(string $storeId, string $storeKeeperId, array $paymentIds = []): void
    {
        //todo
        $this->getShopModule($storeId)->attachPaymentIdsToOrder(['payment_ids' => $paymentIds], $storeKeeperId);
    }

    /**
     * @param string $storeId
     * @return ModuleApiWrapperInterface
     * @throws \Exception
     */
    private function getShopModule(string $storeId): ModuleApiWrapperInterface
    {
        return $this->orderApiClient->getShopModule($storeId);
    }

    /**
     * @param string $storeId
     * @return array
     * @throws \Exception
     */
    public function getListTranslatedPaymentMethodForHooks(string $storeId): array
    {
        return $this->getShopModule($storeId)->listTranslatedPaymentMethodForHooks('0', 0, 10, null, []);
    }

    /**
     * @param string $storeId
     * @param string $storekeeperPaymentId
     * @return array
     * @throws \Exception+
     */
    public function syncWebShopPaymentWithReturn(string $storeId, string $storekeeperPaymentId): array
    {
        return $this->getShopModule($storeId)->syncWebShopPaymentWithReturn($storekeeperPaymentId);
    }
}
