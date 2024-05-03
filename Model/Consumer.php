<?php

namespace StoreKeeper\StoreKeeper\Model;

use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Categories;
use StoreKeeper\StoreKeeper\Helper\Api\Orders;
use StoreKeeper\StoreKeeper\Helper\Api\Products;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;

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
    private Config $configHelper;
    private LoggerInterface $logger;
    private Auth $authHelper;

    /**
     * Constructor
     *
     * @param Products $productsHelper
     * @param Categories $categoriesHelper
     * @param Orders $ordersHelper
     * @param LoggerInterface $logger
     * @param Auth $authHelper
     */
    public function __construct(
        Products $productsHelper,
        Categories $categoriesHelper,
        Orders $ordersHelper,
        LoggerInterface $logger,
        Auth $authHelper
    ) {
        $this->productsHelper = $productsHelper;
        $this->categoriesHelper = $categoriesHelper;
        $this->ordersHelper = $ordersHelper;
        $this->logger = $logger;
        $this->authHelper = $authHelper;
    }

    /**
     * Process
     *
     * @param string $request
     * @return void
     */
    public function process($request)
    {
        $data = json_decode($request, true);
        if ($data['type'] == 'disconnect') {
            $this->productsHelper->cleanProductStorekeeperId($data['storeId']);
            $this->authHelper->disconnectStore($data['storeId']);
        } else {
            try {
                $storeId = $data['storeId'] ?? null;
                $module = $data['module'];
                $entity = $data['entity'];
                $key = $data['key'];
                $value = $data['value'];
                $type = $data['type'];
                $refund = $data['refund'] ?? false;

                if (is_null($storeId)) {
                    throw new \Exception("Missing store ID");
                }

                if ($type == "updated") {
                    if ($entity == "ShopProduct") {
                        $this->productsHelper->updateById($storeId, $value);
                        $this->productsHelper->updateStock($storeId, $value);
                    } elseif ($entity == 'Category') {
                        $this->categoriesHelper->updateById($storeId, $value);
                    } elseif ($entity == "Order") {
                        $this->ordersHelper->updateById($storeId, $value, $refund);
                    }
                } elseif ($type == "deactivated") {
                    if ($entity == "ShopProduct") {
                        $this->productsHelper->onDeactivate($storeId, $value);
                    } elseif ($entity == 'Category') {
                        $this->categoriesHelper->onDeactivate($storeId, $value);
                    }
                } elseif ($type == "activated") {
                    if ($entity == "ShopProduct") {
                        $this->productsHelper->activate($storeId, $value);
                    } elseif ($entity == 'Category') {
                        $this->categoriesHelper->activate($storeId, $value);
                    }
                } elseif ($type == "deleted") {
                    if ($entity == 'Category') {
                        $this->categoriesHelper->onDeleted($storeId, $value);
                    }
                } elseif ($type == "created") {
                    if ($entity == 'Category') {
                        $this->categoriesHelper->onCreated($storeId, $value);
                    } elseif ($entity == "ShopProduct") {
                        $this->productsHelper->updateById($storeId, $value);
                    }
                } elseif ($type == 'stock_change') {
                    $this->productsHelper->updateStock($storeId, $value);
                }
            } catch (\Exception $e) {
                $this->logger->error("[{$type}] {$entity}({$value}): {$e->getMessage()}");
            }
        }
    }
}
