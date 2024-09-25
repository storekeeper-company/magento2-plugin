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
     * @param string|int $storekeeperId
     * @return array
     * @throws \Exception
     */
    public function getStoreKeeperOrder(string $storeId, $storekeeperId): array
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

    /**
     * @param string $storeId
     * @return mixed
     */
    public function getStoreInformation(string $storeId): mixed
    {
        return $this->getShopModule($storeId)->getShopSettingsForHooks();
    }

    /**
     * @param string $storeId
     * @param array $status
     * @param string $storeKeeperId
     * @throws \Exception
     */
    public function updateOrderStatus(string $storeId, array $status, string $storeKeeperId): void
    {
        $this->getShopModule($storeId)->updateOrderStatus($status, $storeKeeperId);
    }

    /**
     * @param string $storeId
     * @param array $payload
     * @param string $storeKeeperId
     * @throws \Exception
     */
    public function updateOrder(string $storeId, array $payload, string $storeKeeperId): void
    {
        $this->getShopModule($storeId)->updateOrder($payload, $storeKeeperId);
    }

    /**
     * @param string $storeId
     * @param array $payload
     * @return array
     * @throws \Exception
     */
    public function getNewOrderWithReturn(string $storeId, array $payload): array
    {
        return $this->getShopModule($storeId)->newOrderWithReturn($payload);
    }

    /**
     * @param string $language
     * @param string $storeId
     * @param string $storeKeeperId
     * @return array
     * @throws \Exception
     */
    public function getNaturalSearchShopFlatProductForHooks(string $language, string $storeId, string $storeKeeperId): array
    {
        return $this->getShopModule($storeId)->naturalSearchShopFlatProductForHooks(
            ' ',
            $language,
            0,
            1,
            [],
            [
                [
                    'name' => 'id__=',
                    'val' => $storeKeeperId
                ]
            ]
        );
    }

    /**
     * @param $storeId
     * @param $storekeeperProductId
     * @return array
     * @throws \Exception
     */
    public function getUpsellShopProductIds($storeId, $storekeeperProductId): array
    {
        return $this->getShopModule($storeId)->getUpsellShopProductIds($storekeeperProductId);
    }

    /**
     * @param $storeId
     * @param $storekeeperProductId
     * @return array
     * @throws \Exception
     */
    public function getCrossSellShopProductIds($storeId, $storekeeperProductId): array
    {
        return $this->getShopModule($storeId)->getCrossSellShopProductIds($storekeeperProductId);
    }

    /**
     * @param string $language
     * @param string $storeId
     * @param string $storeKeeperId
     * @return mixed
     * @throws \Exception
     */
    public function getConfigurableShopProductOptions(string $language, string $storeId, string $storeKeeperId)
    {
        return $this->getShopModule($storeId)->getConfigurableShopProductOptions(
            $storeKeeperId,
            ['lang' => $language]
        );
    }

    /**
     * @param int|string $storeId
     * @return array
     * @throws \Exception
     */
    public function getListShippingMethodsForHooks(int|string $storeId): array
    {
        return $this->getShopModule($storeId)->listShippingMethodsForHooks(0, 999, null, null);
    }

    /**
     * @param int $storekeeperId
     * @param array $items
     * @param string $storeId
     * @param string|null $parcelNumber
     * @param string|null $parcelTrackTraceUrl
     * @return int
     * @throws \Exception
     */
    public function newOrderShipment(string $storekeeperId, array $items, string $storeId, ?string $parcelNumber = null,  ?string $parcelTrackTraceUrl = null): int
    {
        $shipment = [
            'order_id' => $storekeeperId,
            'order_items' => $items,
            'force_negative_stock_if_missing' => true, // retailer need to fix it manually later
        ];

        if (!empty($parcelNumber)){
            $shipment['parcel'] = [
                'eid' => $parcelNumber,
                'parcels_in_group' => [[
                    'weight_g' => '1000',
                    'tracking_number' => $parcelNumber,
                    'tracking_url' => $parcelTrackTraceUrl,
                ]],
            ];
        }
        $shipmentId = $this->getShopModule($storeId)->newOrderShipmentForHook($shipment);

        return $shipmentId;
    }

    /**
     * @param string $storeId
     * @param int $shipmentId
     * @return void
     * @throws \Exception
     */
    public function markOrderShipmentDelivered(string $storeId, int $shipmentId): void
    {
        $this->getShopModule($storeId)->markOrderShipmentAsDeliveredForHook($shipmentId);
    }

    /**
     * Get list translated category for hooks
     *
     * @param $storeId
     * @param $language
     * @param int $offset
     * @param int $limit
     * @param array $order
     * @param array $filters
     * @return mixed
     * @throws \Exception
     */
    public function listTranslatedCategoryForHooks(
        $storeId,
        $language,
        int $offset,
        int $limit,
        array $order,
        array $filters
    ) {
        return $this->getShopModule($storeId)->listTranslatedCategoryForHooks(
            $language,
            $offset,
            $limit,
            $order,
            $filters
        );
    }
}
