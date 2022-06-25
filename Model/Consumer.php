<?php


namespace StoreKeeper\StoreKeeper\Model;

use StoreKeeper\StoreKeeper\Helper\Api\Categories;
use StoreKeeper\StoreKeeper\Helper\Api\Orders;
use StoreKeeper\StoreKeeper\Helper\Api\Products;
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

    public function __construct(
        Products $productsHelper,
        Categories $categoriesHelper,
        Orders $ordersHelper,
        Config $configHelper
    ) {
        $this->productsHelper = $productsHelper;
        $this->categoriesHelper = $categoriesHelper;
        $this->ordersHelper = $ordersHelper;
        $this->configHelper = $configHelper;
    }

    private function getMode($storeId)
    {
        return $this->configHelper->getMode($storeId);
    }

    /**
     * Process
     *
     * @param string $request
     * @return void
     */
    public function process($request)
    {
        var_dump('start');
        $data = json_decode($request, true);
        $storeId = $data['storeId'] ?? null;
        $module = $data['module'];
        $entity = $data['entity'];
        $key = $data['key'];
        $value = $data['value'];
        $type = $data['type'];

        if (is_null($storeId)) {
            throw new \Exception("Missing store ID");
        }

        $mode = $this->getMode($storeId);

        try {
            if ($type == "updated") {
                if ($entity == "ShopProduct") {
                    if ($mode === 'default') {
                        $this->productsHelper->updateById($storeId, $value);
                    } elseif ($mode === 'order_only_mode') {
                        $this->productsHelper->updateStock($storeId, $value);
                    }
                } else if ($entity == 'Category' && $mode === 'default') {
                    $this->categoriesHelper->updateById($storeId, $value);
                } else if ($entity == "Order" && ($mode === 'order_only_mode' || $mode === 'default')) {
                    $this->ordersHelper->updateById($storeId, $value);
                }
            } else if ($type == "deactivated" && $mode === 'default') {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->onDeactivate($storeId, $value);
                } else if ($entity == 'Category' && $mode === 'default') {
                    $this->categoriesHelper->onDeactivate($storeId, $value);
                }
            } else if ($type == "activated" && $mode === 'default') {
                if ($entity == "ShopProduct") {
                    $this->productsHelper->onActivate($storeId, $value);
                } else if ($entity == 'Category') {
                    $this->categoriesHelper->onActivate($storeId, $value);
                }
            } else if ($type == "deleted" && $mode === 'default') {
                if ($entity == 'Category') {
                    $this->categoriesHelper->onDeleted($storeId, $value);
                }
            } else if ($type == "created" && $mode === 'default') {
                if ($entity == 'Category') {
                    $this->categoriesHelper->onCreated($storeId, $value);
                }
            } else if ($type == 'stock_change' && $mode === 'order_only_mode') {
                $this->productsHelper->updateStock($storeId, $value);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
