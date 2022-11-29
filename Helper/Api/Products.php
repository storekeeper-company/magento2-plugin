<?php

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
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
use Magento\Store\Model\Store;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\AttributeFilter;

use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;

class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    const API_URL = 'https://api-creativectdev.storekeepercloud.com';

    private SourceItemsSaveInterface $sourceItemsSave;

    private SourceItemInterfaceFactory $sourceItemFactory;

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
        \Magento\CatalogInventory\Model\Stock\Item $stockItem,
        AttributeFilter $attributeFilter,
        DirectoryList $directoryList,
        File $file,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        ProductLinkInterfaceFactory $productLinkFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry, 
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
        $this->attributeFilter = $attributeFilter;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productLinkFactory = $productLinkFactory;
        $this->stockRegistry = $stockRegistry;
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

    public function updateStock($storeId, $storeKeeperId)
    {
        $language = $this->authHelper->getLanguageForStore($storeId);

        $results = $this->authHelper->getModule('ShopModule', $storeId)->naturalSearchShopFlatProductForHooks(
            ' ',
            $language,
            0,
            1,
            [],
            [
                [
                    'name' => 'flat_product/product_id__=',
                    'val' => $storeKeeperId
                ]
            ]
        );

        if (isset($results['data']) && count($results['data']) > 0) {
            $result = $results['data'][0];
            $product_stock = $result['flat_product']['product']['product_stock'];

            if ($product = $this->exists($storeId, $result)) {
                $this->updateProductStock($storeId, $product, $product_stock);
            } else {
                echo "Can't update product because it doesn't exist\n";
            }
        } else {
            throw new \Exception("Product {$storeKeeperId} does not exist in StoreKeeper");
        }
    }

    /**
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws Exception
     */
    private function updateProductStock($storeId, $product, $product_stock)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId()); 

        if ($stockItem) {
            if ($stockItem->getManageStock()) {
                $stockItem->setData('is_in_stock', $product_stock['value'] > 0); 
                $stockItem->setData('qty', $product_stock['value']); 
                $stockItem->setData('use_config_notify_stock_qty',1);
                $stockItem->save(); 
                $product->save();
            }
        // in some rare cases it can occur that a stock item doesn't exist in Magento 2
        // if there's no existing stock item, we'll create it
        } else {
            $stockItem->setData('is_in_stock', $product_stock['value'] > 0); 
            $stockItem->setData('qty', $product_stock['value']); 
            $stockItem->setData('manage_stock',true);
            $stockItem->setData('use_config_notify_stock_qty',1);
            $stockItem->save(); 
            $product->save();
        }
    }

    public function updateById($storeId, $storeKeeperId)
    {
        $language = $this->authHelper->getLanguageForStore($storeId);

        $results = $this->authHelper->getModule('ShopModule', $storeId)->naturalSearchShopFlatProductForHooks(
            ' ',
            $language,
            0,
            1,
            [],
            [
                [
                    'name' => 'flat_product/product_id__=',
                    'val' => $storeKeeperId
                ]
            ]
        );


        if (isset($results['data']) && count($results['data']) > 0) {
            $result = $results['data'][0];
            file_put_contents("update_products.{$storeId}.json", json_encode($result, JSON_PRETTY_PRINT), FILE_APPEND);
            if ($product = $this->exists($storeId, $result)) {
                $this->update($storeId, $product, $result);
            } else {
                $this->onCreate($storeId, $result);
            }
        } else {
            throw new \Exception("Product {$storeKeeperId} does not exist in StoreKeeper");
        }
    }

    public function updateProductLinks($storeId, $target, $storeKeeperEndpoint = 'getUpsellShopProductIds', $linkType = 'upsell')
    {
        $storekeeperProductId = $target->getStorekeeperProductId();
        if (empty($storeKeeperProductId)) {
            $storekeeperProductId = $target->getData()['storekeeper_product_id'];
        }

        if (empty($storekeeperProductId)) {
            throw new \Exception("Missing 'storekeeper_product_id' for {$target->getSku()}");
        }
        $storekeeperLinkIds = $this->authHelper->getModule('ShopModule', $storeId)->$storeKeeperEndpoint($storekeeperProductId);

        $storekeeperLinkSkus = [];
        
        foreach ($storekeeperLinkIds as $storekeeperLinkId) {
            if ($linkedProduct = $this->exists($storeId, ['product_id' => $storekeeperLinkId])) {
                $storekeeperLinkSkus[] = $linkedProduct->getSku();
            }
        }

        $filtered = array_filter($target->getProductLinks(), function ($link) use ($linkType) {
            return $link->getLinkType() == $linkType;
        });

        $currentLinkSkus = array_map(function ($link) {
            return $link->getLinkedProductSku();
        }, $filtered);

        ksort($storekeeperLinkSkus);
        ksort($currentLinkSkus);

        if ($storekeeperLinkSkus !== $currentLinkSkus) {

            // filter all upsell
            $linkData = array_filter($target->getProductLinks(), function ($link) use ($linkType) {
                return $link->getLinkType() != $linkType;
            });

            foreach ($storekeeperLinkSkus as $index => $storekeeperLinkSku) {
                $productLink = $this->productLinkFactory->create();
                $linkData[] = $productLink->setSku($target->getSku())
                    ->setLinkedProductSku($storekeeperLinkSku)
                    ->setPosition($index)
                    ->setLinkType($linkType);
            }

            $target->setProductLinks($linkData);


            return true;
        }

        return false;
    }

    // public function updateUpsells($storeId, $target)
    // {
    //     $storekeeperProductId = $target->getStorekeeperProductId();
    //     if (empty($storeKeeperProductId)) {
    //         $storekeeperProductId = $target->getData()['storekeeper_product_id'];
    //     }

    //     if (empty($storekeeperProductId)) {
    //         throw new \Exception("Missing 'storekeeper_product_id' for {$target->getSku()}");
    //     }
    //     $upsellProductIds = $this->authHelper->getModule('ShopModule', $storeId)->getUpsellShopProductIds($storekeeperProductId);

    //     $upsellSkus = [];
    //     foreach ($upsellProductIds as $upsellProductId) {
    //         if ($upsell = $this->exists($storeId, [ 'product_id' => $upsellProductId ])) {
    //             $upsellSkus[] = $upsell->getSku();
    //         }
    //     }

    //     $filtered = array_filter($target->getProductLinks(), function ($link) {
    //         return $link->getLinkType() == "upsell";
    //     });

    //     $currentUpsellSkus = array_map(function ($link) {
    //         return $link->getLinkedProductSku();
    //     }, $filtered);

    //     if ($upsellSkus !== $currentUpsellSkus) {

    //         // filter all upsell
    //         $linkData = array_filter($target->getProductLinks(), function ($link) {
    //             return $link->getLinkType() != 'upsell';
    //         });

    //         foreach ($upsellSkus as $index => $upsellSku) {
    //             $productLink = $this->productLinkFactory->create();
    //             $linkData[] = $productLink->setSku($target->getSku())
    //                 ->setLinkedProductSku($upsellSku)
    //                 ->setPosition($index)
    //                 ->setLinkType('upsell');
    //         }

    //         $target->setProductLinks($linkData);


    //         return true;
    //     }

    //     return false;
    // }

    // public function updateCrosssells($storeId, $target)
    // {
    //     $storekeeperProductId = $target->getStorekeeperProductId();
    //     if (empty($storeKeeperProductId)) {
    //         $storekeeperProductId = $target->getData()['storekeeper_product_id'];
    //     }

    //     if (empty($storekeeperProductId)) {
    //         throw new \Exception("Missing 'storekeeper_product_id' for {$target->getSku()}");
    //     }
    //     $crosssellProductIds = $this->authHelper->getModule('ShopModule', $storeId)->getCrossSellShopProductIds($storekeeperProductId);

    //     $crossellSkus = [];
    //     foreach ($crosssellProductIds as $crosssellProductId) {
    //         if ($crossell = $this->exists($storeId, ['product_id' => $crosssellProductId])) {
    //             $crossellSkus[] = $crossell->getSku();
    //         }
    //     }


    //     $filtered = array_filter($target->getProductLinks(), function ($link) {
    //         return $link->getLinkType() == "crosssell";
    //     });

    //     $currentCrosssellSkus = array_map(function ($link) {
    //         return $link->getLinkedProductSku();
    //     }, $filtered);


    //     if ($crossellSkus !== $currentCrosssellSkus) {

    //         // filter all related
    //         $linkData = array_filter($target->getProductLinks(), function($link) {
    //             return $link->getLinkType() != 'crosssell';
    //         });

    //         foreach ($crossellSkus as $index => $upsellSku) {
    //             $productLink = $this->productLinkFactory->create();
    //             $linkData[] = $productLink->setSku($target->getSku())
    //                 ->setLinkedProductSku($upsellSku)
    //                 ->setPosition($index)
    //                 ->setLinkType('crosssell');
    //         }

    //         $target->setProductLinks($linkData);


    //         return true;
    //     }

    //     return false;
    // }

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

    public function onCreate($storeId, array $result) //, array $shopProductAssigns)
    {
        return $this->update($storeId, null, $result); //, $shopProductAssigns);
    }

    public function onDeactivate($storeId, $targetId)
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

    public function update($storeId, $target = null, array $result = []) //, array $shopProductAssigns)
    {
        $this->storeManager->setCurrentStore($storeId);

        $language = $this->authHelper->getLanguageForStore($storeId);

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

            if ($language == " ") {
                $target->setStoreId(Store::DEFAULT_STORE_ID);
            }

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

            if (isset($flat_product['main_image'])) {
                $existingImage = null;
                $newImagePath = explode('/', parse_url($flat_product['main_image']['big_url'], PHP_URL_PATH));
                $newImageName = pathinfo(end($newImagePath), PATHINFO_FILENAME);

                if ($target->getImage()) {
                    $existingImagePath = explode('/', $target->getImage());
                    $existingImage = pathinfo(end($existingImagePath), PATHINFO_FILENAME);
                }

                if ($existingImage) {
                    if ($existingImage == 'no_selection' || !preg_match("/^{$newImageName}\_[0-9]+/", $existingImage)) {
                        $shouldUpdate = true;
                        $this->setGalleryImage($flat_product['main_image']['big_url'], $target, true);
                    }
                }
            } else {
                //ToDo: remove main image
            }

            $galleryImages = $target->getMediaGalleryImages()->getItems();
            $existingImagesArray = [];
            foreach ($galleryImages as $image) {
                $imagePath = explode('/', parse_url($image->getFile(), PHP_URL_PATH));
                $imageName = pathinfo(end($imagePath), PATHINFO_FILENAME);
                $existingImagesArray[] = $imageName;
            }

            if (isset($flat_product['product_images'])) {
                $mainImage = explode('/', $flat_product['main_image']['big_url']);
                $mainImageName = pathinfo(end($mainImage), PATHINFO_FILENAME);
                foreach ($flat_product['product_images'] as $product_image) {
                    $newImagePath = explode('/', parse_url($product_image['big_url'], PHP_URL_PATH));
                    $newImageName = pathinfo(end($newImagePath), PATHINFO_FILENAME);

                    $countDuplicates = count(preg_grep("/^{$newImageName}\_[0-9]+/", $existingImagesArray));
                    $shouldUpdate = true;

                    if ($newImageName !== $mainImageName && !preg_match("/^{$newImageName}\_[0-9]+/", $mainImageName)) {
                        if (!in_array($newImageName, $existingImagesArray) && $countDuplicates <= 0) {
                            $this->setGalleryImage($product_image['big_url'], $target, false);
                        }
                    }
                }
            }

            if ($this->updateProductLinks($storeId, $target, 'getUpsellShopProductIds', 'upsell')) {
                $shouldUpdate = true;
                echo "  Should update upsells\n";
            }

            if ($this->updateProductLinks($storeId, $target, 'getCrossSellShopProductIds', 'crosssell')) {
                $shouldUpdate = true;
                echo "  Should update crosssells\n";
            }

            if ($shouldUpdate) {
                $this->productRepository->save($target);

                if ($update) {
                    echo "  Updated {$sku} ({$title})\n";

                    if ($language == ' ') {
                        $this->setProductToUseDefaultValues($target, $storeId);
                    }
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

            if ($product_stock_unlimited &&
                ($stockItem->GetBackorders() == false || $stockItem->getUseConfigBackOrders() == true)
            ) {
                $stockItem->setBackorders(true);
                $stockItem->setUseConfigBackorders(false);
            } elseif (!$product_stock_unlimited &&
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

            if (!empty($categoryIds = $this->getResultCategoryIds($result))) {
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

    /**
     * @param $image
     * @param $target Product
     * @param $mainImage
     */
    private function setGalleryImage($image, Product $target, $mainImage)
    {
        $tmpDir = $this->getMediaTmpDir();
        $url = self::API_URL . parse_url($image, PHP_URL_PATH);
        $newImage = $tmpDir . baseName($url);
        $result = $this->file->read($url, $newImage);
        $imageTypes = [];

        if ($mainImage) {
            $imageTypes = ['image', 'small_image', 'thumbnail'];
        }

        if ($result) {
            try {
                $target->addImageToMediaGallery($newImage, $imageTypes, true, false);
            } catch (LocalizedException $e) {
                var_dump($e->getMessage());
                var_dump($e->getTraceAsString());
            }
        }
    }

    private function getMediaTmpDir()
    {
        $tmpDir = $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'import/';
        $this->file->checkAndCreateFolder($tmpDir);

        return $tmpDir;
    }

    private function getResultStoreKeeperId($result)
    {
        return $result['product_id'];
    }

    private function getResultSku($result)
    {
        return $result['flat_product']['product']['sku'];
    }

    private function getResultCategoryIds($result)
    {
        return array_map(function ($cat) {
            return $cat['id'];
        }, $result['flat_product']['categories'] ?? []);
    }

    private function setProductToUseDefaultValues($target, $storeId)
    {
        echo "      Setting \"{$target->getSku()}\" for store \"{$storeId}\" to use default values\n";

        $target->setStoreId($storeId);

        $productData = $target->getData();

        $productData['name'] = null;
        $productData['description'] = false;
        $productData['short_description'] = false;
        $productData['meta_title'] = false;
        $productData['meta_description'] = false;
        $productData['url_key'] = false;

        $target->setData($productData);

        $this->productRepository->save($target);
    }
}
