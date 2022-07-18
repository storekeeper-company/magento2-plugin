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
        Orders $ordersHelper
        // Config $configHelper
    ) {
        $this->productsHelper = $productsHelper;
        $this->categoriesHelper = $categoriesHelper;
        $this->ordersHelper = $ordersHelper;
        // $this->configHelper = $configHelper;
    }

    // private function getMode($storeId)
    // {
    //     return $this->configHelper->getMode($storeId);
    // }

    // private function hasMode($storeId, $flags)
    // {
    //     return $this->configHelper->hasMode($storeId, $flags);
    // }

    /**
     * Process
     *
     * @param string $request
     * @return void
     */
    public function process($request)
    {
        // echo "Working!\n";

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

        // if ($entity == "ShopProduct" && !$this->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
        //     echo "Products are not allowed\n";
        //     return false;
        // } else if ($entity == "Category" && !$this->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
        //     echo "Categories are not allowed\n";
        //     return false;
        // } else if ($entity == "Order" && !$this->hasMode($storeId, Config::SYNC_ORDERS | Config::SYNC_ALL)) {
        //     echo "Orders are not allowed\n";
        //     return false;
        // } else if ($type =="stock_change" && !$this->hasMode($storeId, Config::SYNC_PRODUCTS | Config::SYNC_ALL)) {
        //     echo "Stock changes are not allowed";
        //     return false;
        // }

        try {
            if ($type == "updated") {

                if ($entity == "ShopProduct") {

                    $this->productsHelper->updateById($storeId, $value);
                    $this->productsHelper->updateStock($storeId, $value);

                } else if ($entity == 'Category') {
                    $this->categoriesHelper->updateById($storeId, $value);

                } else if ($entity == "Order") {
                    $this->ordersHelper->updateById($storeId, $value);
                }

            } else if ($type == "deactivated") {

                if ($entity == "ShopProduct") {
                    $this->productsHelper->onDeactivate($storeId, $value);
                } else if ($entity == 'Category') {
                    $this->categoriesHelper->onDeactivate($storeId, $value);
                }

            } else if ($type == "activated") {

                if ($entity == "ShopProduct") {
                    $this->productsHelper->activate($storeId, $value);
                } else if ($entity == 'Category') {
                    $this->categoriesHelper->activate($storeId, $value);
                }

            } else if ($type == "deleted") {

                if ($entity == 'Category') {
                    $this->categoriesHelper->onDeleted($storeId, $value);
                }

            } else if ($type == "created") {

                if ($entity == 'Category') {
                    $this->categoriesHelper->onCreated($storeId, $value);
                }

            } else if ($type == 'stock_change') {

                $this->productsHelper->updateStock($storeId, $value);

            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}
