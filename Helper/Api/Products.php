<?php

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Exception;
use Magento\Framework\Api\FilterFactory;
use Parsedown;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ProductLink\Repository as ProductLinkRepository;
use Magento\Store\Model\StoreManager;

class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        Auth $authHelper,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryRepository $categoryRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogInventory\Model\Stock\Item $stockItem
    ) {
        $this->authHelper = $authHelper;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->storeManager = $storeManager;

        $this->storeShopIds = $this->authHelper->getStoreShopIds();
        $this->websiteShopIds = $this->authHelper->getWebsiteShopIds();
        $this->stockItem = $stockItem;
    }

    public function authCheck($storeId)
    {
        return $this->authHelper->authCheck($storeId);
    }

    public function getLanguageForStore($storeId)
    {
        return $this->authHelper->getLanguageForStore($storeId);
    }

    public function naturalSearchShopFlatProducts(
        $storeId,
        string $query,
        string $lang,
        int $start,
        int $limit,
        array $order,
        array $filters
    ) {
        return $this->authHelper->getModule('ShopModule', $storeId)->naturalSearchShopFlatProducts(
            $query,
            $lang,
            $start,
            $limit,
            $order,
            $filters
        );
    }

    public function naturalSearchShopFlatProductForHooks(
        $storeId,
        string $query,
        string $lang,
        int $start,
        int $limit,
        array $order,
        array $filters
    ) {
        return $this->authHelper->getModule('ShopModule', $storeId)->naturalSearchShopFlatProductForHooks(
            $query,
            $lang,
            $start,
            $limit,
            $order,
            $filters
        );
    }

    public function listShopShopProductAssigns(
        $storeId,
        int $start,
        int $limit,
        array $order,
        array $filters
    ) {
        return $this->authHelper->getModule('ShopModule', $storeId)->listShopShopProductAssigns(
            $start,
            $limit,
            $order,
            $filters
        );
    }

    public function updateById($storeId, $storeKeeperId)
    {
        $results = $this->authHelper->getModule('ShopModule', $storeId)->naturalSearchShopFlatProductForHooks(
            ' ',
            ' ',
            0,
            1,
            [],
            [
                [
                    'name' => 'flat_product/id__=',
                    'val' => $storeKeeperId
                ]
            ]
        );

        if (isset($results['data']) && count($results['data']) > 0) {
            $result = $results['data'][0];
            if ($product = $this->exists($storeId, $result)) {
                $this->update($storeId, $product, $result);
            } else {
                $this->create($storeId, $result);
            }
        } else {
            echo "does not exist in storekeeper\n";
        }
    }

    public function exists($storeId, array $result)
    {
        $storekeeper_id = $this->getResultStoreKeeperId($result);
        try {

            $collection = $this->productCollectionFactory->create();
            $collection
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('storekeeper_product_id', $storekeeper_id)
                ->setFlag('has_stock_status_filter', false);

            if ($collection->count()) {
                $firstItem = $collection->getFirstItem();
                return $firstItem;
            }

            try {
                $storekeeper_sku = $this->getResultSku($result);

                if ($result = $this->productRepository->get($storekeeper_sku)) {
                    return $result;
                }
            } catch (Exception $e) {
                // ignoring
            }

            return false;
        } catch (\Exception $e) {
            echo "\n\n";
            echo "  {$e->getMessage()}";
            echo "\n\n";
            exit;
        }
        return false;
    }

    public function categoriesExist($storekeeper_category_ids)
    {
        if (count($storekeeper_category_ids) > 0) {
            $collection = $this->categoryCollectionFactory->create();
            $collection
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('storekeeper_category_id', array('in', $storekeeper_category_ids))
                ->setFlag('has_stock_status_filter', false);

            if ($collection->count() > 0) {
                return $collection->getItems();
            }

            return false;
        }
    }


    private $websiteIds = [];

    private function getStoreWebsiteId($storeId)
    {
        if (!isset($this->websiteIds[$storeId])) {
            $store = $this->storeManager->getStore($storeId);
            $this->websiteIds[$storeId] = $store->getWebsiteId();
        }

        return $this->websiteIds[$storeId];
    }

    public function create($storeId, array $result) //, array $shopProductAssigns)
    {
        return $this->update($storeId, null, $result); //, $shopProductAssigns);
    }

    public function deactivate($storeId, $targetId)
    {
        $websiteId = $this->getStoreWebsiteId($storeId);

        if ($target = $this->exists($storeId, [
            'product_id' => $targetId
        ])) {
            if (in_array($websiteId, $target->getWebsiteIds())) {
                $target->setWebsiteIds(array_diff($target->getWebsiteIds(), [$websiteId]));
                $this->productRepository->save($target);
            }
        } else {
            $this->updateById($storeId, $targetId);
        }
    }

    public function activate($storeId, $targetId)
    {
        $websiteId = $this->getStoreWebsiteId($storeId);
        if ($target = $this->exists($storeId, [
            'product_id' => $targetId
        ])) {
            $websiteIds = $target->getWebsiteIds();
            if (!in_array($websiteId, $websiteIds)) {
                $websiteIds[] = $websiteId;
                $target->setWebsiteIds($websiteIds);
                $target = $this->productRepository->save($target);
            }
        } else {
            $this->updateById($websiteId, $targetId);
        }
    }

    public function update($storeId, $target = null, array $result) //, array $shopProductAssigns)
    {
        $this->storeManager->setCurrentStore($storeId);


        $websiteId = $this->getStoreWebsiteId($storeId);

        $flat_product = $result['flat_product'];
        $product_price = $result['product_price'];

        $price = $product_price['ppu'];
        $price_wt = $product_price['ppu_wt'];

        $title = $flat_product['title'];

        $summary = $flat_product['summary'] ?? '';
        $body = $flat_product['body'];
        $slug = $flat_product['slug'];

        $product = $flat_product['product'];

        $sku = $product['sku'];

        $type = $product['type'];
        $shouldUpdate = false;

        if ($type == 'simple') {

            $update = !is_null($target);
            $create = !$update;

            if ($update) {
                $target = $this->productFactory->create()->load($target->getId());
            } else {
                $shouldUpdate = true;
                $target = $this->productFactory->create();
            }

            $target->setStoreId($storeId);

            $newStatus = $product['active'] ?
                Status::STATUS_ENABLED :
                Status::STATUS_DISABLED;

            if ($target->getStatus() != $newStatus) {
                $target->setStatus($newStatus);
            }

            if ($create) {
                $target->setAttributeSetId(4); // default
                $target->setSku($sku);
            }

            if ($target->getName() != $title) {
                $shouldUpdate = true;
                $target->setName($title);
            }
            if ((float) $target->getPrice() != (float) $price_wt) {
                $shouldUpdate = true;
                $target->setPrice($price_wt);
            }



            $storekeeper_id = $this->getResultStoreKeeperId($result);

            if ($target->getStoreKeeperProductId() != $storekeeper_id) {
                $target->setStorekeeperProductId($storekeeper_id);
            }


            $seo_title = $flat_product['seo_title'] ?? null;
            if ($target->getMetaTitle() != $seo_title) {
                $shouldUpdate = true;
                $target->setMetaTitle($seo_title);
            }

            $seo_description = $flat_product['seo_description'] ?? null;
            if ($target->getMetaDescription() != $seo_description) {
                $shouldUpdate = true;
                $target->setMetaDescription($seo_description);
            }

            if ($target->getUrlKey() != $slug) {
                $shouldUpdate = true;
                $target->setUrlKey($slug);
            }



            $parseDown = new Parsedown();

            $newDescription = $parseDown->text($body);
            if ($target->getDescription() != $newDescription) {
                $shouldUpdate = true;
                $target->setDescription($newDescription);
            }

            $newShortDescription = $parseDown->text($summary);
            if ($target->getShortDescription() != $newShortDescription) {
                $shouldUpdate = true;
                $target->setShortDescription($newShortDescription);
            }

            $websiteId = $this->getStoreWebsiteId($storeId);
            $websiteIds = $target->getWebsiteIds();

            if (!in_array($websiteId, $websiteIds)) {
                $shouldUpdate = true;
                $websiteIds[] = $websiteId;
                $target->setWebsiteIds($websiteIds);
            }

            if ($shouldUpdate) {
                $this->productRepository->save($target);
                if ($update) {
                    echo "  Updated {$sku} ({$title})\n";
                } else {
                    echo "  Created {$sku} ({$title})\n";
                }
            } else {
                echo "  Skipped {$sku} ({$title}), no changes\n";
            }

            $shouldUpdateStock = false;
            $stockItem = $this->stockItem->load($target->getId(), 'product_id');

            (float) $product_stock_value = $product['product_stock']['value'];
            $product_stock_in_stock = $product['product_stock']['in_stock'];
            $product_stock_unlimited = $product['product_stock']['unlimited'];

            if (
                $product_stock_unlimited &&
                ($stockItem->GetBackorders() == false || $stockItem->getUseConfigBackOrders() == true)
            ) {
                $stockItem->setBackorders(true);
                $stockItem->setUseConfigBackorders(false);
            } else if (
                !$product_stock_unlimited &&
                ($stockItem->GetBackorders() == true || $stockItem->getUseConfigBackOrders() == false)
            ) {
                $stockItem->setBackorders(false);
                $stockItem->setUseConfigBackorders(true);
            }

            // prevent managing stock for storekeeper products
            if ($stockItem->getManageStock() || $stockItem->getUseConfigManageStock()) {
                $shouldUpdateStock = true;
                $stockItem->setManageStock(false);
                $stockItem->setUseConfigManageStock(false);
            }

            if (is_null($stockItem->getQty()) || $stockItem->getQty() != $product_stock_value) {
                $shouldUpdateStock = true;
                $stockItem->setQty($product_stock_value);
            }

            if ($shouldUpdateStock) {
                $stockItem->save();

                echo "      Stock has changed, updated stock\n";
                echo "\n";
            }

            if (!empty($storeIds) && !empty($categoryIds = $this->getResultCategoryIds($result))) {
                // assign the store to the first available, otherwise delete operations will go wrong

                // check if categories exist
                if ($categories = $this->categoriesExist($categoryIds)) {
                    $categoryIds = array_map(function ($category) {
                        return $category->getId();
                    }, $categories);

                    try {
                        $diff = array_diff($target->getCategoryIds(), $categoryIds);
                        if (empty($target->getCategoryIds()) || $diff) {
                            echo "  Updating category assignment for {$target->getSku()}";
                            // $this->storeManager->setCurrentStore($storeIds[0]);
                            if ($diff) {
                                foreach ($diff as $categoryId) {
                                    echo ".";
                                    $this->categoryLinkRepository->deleteByIds($categoryId, $target->getSku());
                                }
                            }

                            echo ".";
                            $this->categoryLinkManagement->assignProductToCategories(
                                $target->getSku(),
                                $categoryIds
                            );
                            echo "done\n";
                        }
                    } catch (Exception $e) {
                        echo "\n\n";
                        echo "  Something went wrong: {$e->getMessage()}\n";
                        echo "      Continuing operations\n";
                        echo "\n\n";
                    }
                }
            }
        } else {
            return "Skipping product type {$type} with sku {$sku}\n";
        }
    }

    private function getResultStoreKeeperId($result)
    {
        return $result['product_id'];
    }

    private function getResultSku($result)
    {
        var_dump($result);
        return $result['flat_product']['product']['sku'];
    }

    private function getResultCategoryIds($result)
    {
        return array_map(function ($cat) {
            return $cat['id'];
        }, $result['flat_product']['categories'] ?? []);
    }
}
