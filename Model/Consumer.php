<?php

namespace StoreKeeper\StoreKeeper\Model;

use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Helper\Api\Categories;
use StoreKeeper\StoreKeeper\Helper\Api\Orders;
use StoreKeeper\StoreKeeper\Helper\Api\Products;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

/**
 * Class Consumer used to process OperationInterface messages.
 */
class Consumer
{
    const CONSUMER_NAME = "storekeeper.queue.events";
    const QUEUE_NAME = "storekeeper.queue.events";
    private Products $productsHelper;
    private Categories $categoriesHelper;
    private Orders $ordersHelper;
    private Logger $logger;
    private Auth $authHelper;
    private Config $configHelper;

    /**
     * Constructor
     *
     * @param Products $productsHelper
     * @param Categories $categoriesHelper
     * @param Orders $ordersHelper
     * @param Logger $logger
     * @param Auth $authHelper
     * @param Config $configHelper
     */
    public function __construct(
        Products $productsHelper,
        Categories $categoriesHelper,
        Orders $ordersHelper,
        Logger $logger,
        Auth $authHelper,
        Config $configHelper
    ) {
        $this->productsHelper = $productsHelper;
        $this->categoriesHelper = $categoriesHelper;
        $this->ordersHelper = $ordersHelper;
        $this->logger = $logger;
        $this->authHelper = $authHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Process
     *
     * @param string $request
     * @return void
     */
    public function process($request): void
    {
        $data = json_decode($request, true);

        try {
            $storeId = array_key_exists('storeId', $data) ? $this->authHelper->getStoreId($data['storeId']) : null;
            $type = $data['type'] ?? null;
            $entity = $data['entity'] ?? null;
            $value = $data['value'] ?? null;
            $refund = $data['refund'] ?? false;

            if (is_null($storeId)) {
                throw new \Exception("Missing store ID");
            }

            if ($type == "updated") {
                if ($entity == "ShopProduct") {
                    if($this->configHelper->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
                        $this->productsHelper->updateById($storeId, $value);
                    }
                    $this->productsHelper->updateStock($storeId, $value);
                } elseif ($entity == 'Category') {
                    $this->categoriesHelper->updateById($storeId, $value);
                } elseif ($entity == "Order") {
                    $this->ordersHelper->updateById($storeId, $value, $refund);
                }
            } elseif ($type == "deactivated") {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->onDeactivate($storeId, $value);
                }
            } elseif ($type == "activated") {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->activate($storeId, $value);
                    $this->productsHelper->updateStock($storeId, $value);
                }
            } elseif ($type == "deleted") {
                if ($entity == 'Category') {
                    $this->categoriesHelper->onDeleted($storeId, $value);
                }
            } elseif ($type == "created") {
                if ($entity == 'Category') {
                    $this->categoriesHelper->updateById($storeId, $value);
                } elseif ($entity == "ShopProduct") {
                    $this->productsHelper->updateById($storeId, $value);
                }
            } elseif ($type == 'stock_change') {
                $this->productsHelper->updateStock($storeId, $value);
            } elseif ($type == 'disconnect') {
                $this->productsHelper->cleanProductStorekeeperId($storeId);
                $this->authHelper->disconnectStore($storeId);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "[{$type}] {$entity}({$value}): {$e->getMessage()}",
                $this->logger->buildReportData($e)
            );
        }
    }
}
