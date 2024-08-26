<?php

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper\AttributeFilter;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableResourceModel;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\OptionFactory;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Magento\Store\Model\StoreManagerInterface;
use Parsedown;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Api\ProductApiClient;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Helper\ProductDescription as ProductDescriptionHelper;
use StoreKeeper\StoreKeeper\Model\Export\ProductExportManager;
use Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface;

/**
 * @depracated
 */
class Products extends \Magento\Framework\App\Helper\AbstractHelper
{
    const API_URL = 'https://api-creativectdev.storekeepercloud.com';
    private $websiteIds = [];
    private Auth $authHelper;
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private CollectionFactory $productCollectionFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private CategoryLinkManagementInterface $categoryLinkManagement;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryRepository $categoryRepository;
    private StoreManagerInterface $storeManager;
    private Item $stockItem;
    private AttributeFilter $attributeFilter;
    private DirectoryList $directoryList;
    private File $file;
    private ProductLinkInterfaceFactory $productLinkFactory;
    private StockRegistryInterface $stockRegistry;
    private Logger $logger;
    private ProductApiClient $productApiClient;
    private OrderApiClient $orderApiClient;
    private SourceItemsProcessorInterface $sourceItemsProcessor;
    private Action $productAction;
    private Config $configHelper;
    private Attributes $attributes;
    private TypeFactory $entityTypeFactory;
    private eavConfig $eavConfig;
    private OptionFactory $optionFactory;
    private ConfigurableResourceModel $configurableResourceModel;
    private Configurable $configurable;
    private OptionsFactory $optionsFactory;
    private ProductDescriptionHelper $productDescription;
    private ResourceConnection $resourceConnection;
    private ProductExportManager $productExportManager;
    private array $_simpleProductIds = [];

    /**
     * Constructor
     *
     * @param Auth $authHelper
     * @param ProductFactory $productFactory
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $productCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param CategoryLinkRepositoryInterface $categoryLinkRepository
     * @param CategoryRepository $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param Item $stockItem
     * @param AttributeFilter $attributeFilter
     * @param DirectoryList $directoryList
     * @param File $file
     * @param ProductLinkInterfaceFactory $productLinkFactory
     * @param StockRegistryInterface $stockRegistry
     * @param Logger $logger
     * @param ProductApiClient $productApiClient
     * @param OrderApiClient $orderApiClient
     * @param SourceItemsProcessorInterface $sourceItemsProcessor
     * @param Action $productAction
     * @param Config $configHelper
     * @param Attributes $attributes
     * @param TypeFactory $entityTypeFactory
     * @param EavConfig $eavConfig
     * @param OptionFactory $optionFactory
     * @param ConfigurableResourceModel $configurableResourceModel
     * @param Configurable $configurable
     * @param OptionsFactory $optionsFactory
     * @param ProductDescriptionHelper $productDescription
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Auth $authHelper,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategoryLinkManagementInterface $categoryLinkManagement,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryRepository $categoryRepository,
        StoreManagerInterface $storeManager,
        Item $stockItem,
        AttributeFilter $attributeFilter,
        DirectoryList $directoryList,
        File $file,
        ProductLinkInterfaceFactory $productLinkFactory,
        StockRegistryInterface $stockRegistry,
        Logger $logger,
        ProductApiClient $productApiClient,
        OrderApiClient $orderApiClient,
        SourceItemsProcessorInterface $sourceItemsProcessor,
        Action $productAction,
        Config $configHelper,
        Attributes $attributes,
        TypeFactory $entityTypeFactory,
        EavConfig $eavConfig,
        OptionFactory $optionFactory,
        ConfigurableResourceModel $configurableResourceModel,
        Configurable $configurable,
        OptionsFactory $optionsFactory,
        ProductDescriptionHelper $productDescription,
        ResourceConnection $resourceConnection,
        ProductExportManager $productExportManager
    ) {
        $this->authHelper = $authHelper;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->storeManager = $storeManager;
        $this->stockItem = $stockItem;
        $this->attributeFilter = $attributeFilter;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->productLinkFactory = $productLinkFactory;
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
        $this->productApiClient = $productApiClient;
        $this->orderApiClient = $orderApiClient;
        $this->sourceItemsProcessor = $sourceItemsProcessor;
        $this->productAction = $productAction;
        $this->configHelper = $configHelper;
        $this->attributes = $attributes;
        $this->entityTypeFactory = $entityTypeFactory;
        $this->eavConfig = $eavConfig;
        $this->optionFactory = $optionFactory;
        $this->configurableResourceModel = $configurableResourceModel;
        $this->configurable = $configurable;
        $this->optionsFactory = $optionsFactory;
        $this->productDescription = $productDescription;
        $this->resourceConnection = $resourceConnection;
        $this->productExportManager = $productExportManager;
    }

    /**
     * Auth check
     *
     * @param $storeId
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function authCheck($storeId)
    {
        return $this->authHelper->authCheck($storeId);
    }

    /**
     * Get language for store
     *
     * @param $storeId
     * @return mixed|string
     */
    public function getLanguageForStore($storeId)
    {
        return $this->authHelper->getLanguageForStore($storeId);
    }

    /**
     * Natural search shop flat products
     *
     * @param $storeId
     * @param string $query
     * @param string $lang
     * @param int $start
     * @param int $limit
     * @param array $order
     * @param array $filters
     * @return mixed
     * @throws \Exception
     */
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

    /**
     * Natural search shop flat products for hooks
     *
     * @param $storeId
     * @param string $query
     * @param string $lang
     * @param int $start
     * @param int $limit
     * @param array $order
     * @param array $filters
     * @return mixed
     * @throws \Exception
     */
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

    /**
     * get list of Shop product assigns
     *
     * @param $storeId
     * @param int $start
     * @param int $limit
     * @param array $order
     * @param array $filters
     * @return mixed
     * @throws \Exception
     */
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

    /**
     * Update stock
     *
     * @param $storeId
     * @param $storeKeeperId
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function updateStock($storeId, $storeKeeperId)
    {
        $language = $this->authHelper->getLanguageForStore($storeId);

        $results = $this->orderApiClient->getNaturalSearchShopFlatProductForHooks($language, $storeId, $storeKeeperId);

        if (isset($results['data']) && count($results['data']) > 0) {
            $result = $results['data'][0];
            $product_stock = $result['flat_product']['product']['product_stock'];

            $status = '';
            $exceptionData = [];
            try {
                if ($product = $this->exists($storeId, $result)) {
                    $this->updateProductStock($storeId, $product, $product_stock);
                    $status = ProductApiClient::PRODUCT_UPDATE_STATUS_SUCCESS;
                } else {
                    throw new \Exception("Product with StoreKeerep ID: {$storeKeeperId} does not exist in Magento");
                }
            } catch (\Exception $e) {
                $exceptionData = [
                    'last_error_message' => $e->getMessage(),
                    'last_error_details' => $e->getTraceAsString()
                ];
                $product = !$product ? null : $product;
                $status = ProductApiClient::PRODUCT_UPDATE_STATUS_ERROR;
            }
            $this->productApiClient->setShopProductObjectSyncStatusForHook(
                $storeId,
                $storeKeeperId,
                $product,
                $status,
                $exceptionData
            );
        } else {
            throw new \Exception("Product {$storeKeeperId} does not exist in StoreKeeper");
        }
    }

    /**
     * Update product stock
     *
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Exception
     */
    private function updateProductStock($storeId, $product, $product_stock)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        if ($stockItem) {
            if ($stockItem->getManageStock()) {
                $stockItem->setData('is_in_stock', $product_stock['value'] > 0);
                $stockItem->setData('qty', $product_stock['value']);
                $stockItem->setData('use_config_notify_stock_qty', 1);
                $stockItem->save();

                $product->setStockData(
                    ['qty' => $product_stock['value'], 'is_in_stock' => $product_stock['value'] > 0]
                );
                $product->setQuantityAndStockStatus(
                    ['qty' => $product_stock['value'], 'is_in_stock' => $product_stock['value'] > 0]
                );
                $product->save();
            }
        // in some rare cases it can occur that a stock item doesn't exist in Magento 2
        // if there's no existing stock item, we'll create it
        } else {
            $stockItem->setData('is_in_stock', $product_stock['value'] > 0);
            $stockItem->setData('qty', $product_stock['value']);
            $stockItem->setData('manage_stock', true);
            $stockItem->setData('use_config_notify_stock_qty', 1);
            $stockItem->save();

            $product->setStockData(['qty' => $product_stock['value'], 'is_in_stock' => $product_stock['value'] > 0]);
            $product->setQuantityAndStockStatus(
                ['qty' => $product_stock['value'], 'is_in_stock' => $product_stock['value'] > 0]
            );
            $product->save();
        }
    }

    /**
     * Update product by Id
     *
     * @param $storeId
     * @param $storeKeeperId
     * @return void
     * @throws \Exception
     */
    public function updateById($storeId, $storeKeeperId)
    {
        $language = $this->authHelper->getLanguageForStore($storeId);
        $results = $this->orderApiClient->getNaturalSearchShopFlatProductForHooks($language, $storeId, $storeKeeperId);
        if (isset($results['data']) && count($results['data']) > 0) {
            $result = $results['data'][0];
            if ($product = $this->exists($storeId, $result)) {
                $this->update($storeId, $product, $result);
            } else {
                $this->onCreate($storeId, $result);
            }
        } else {
            throw new \Exception("Product {$storeKeeperId} does not exist in StoreKeeper");
        }
    }

    /**
     * Update product links
     *
     * @param $storeId
     * @param $target
     * @param $storeKeeperEndpoint
     * @param $linkType
     * @return bool
     * @throws \Exception
     */
    public function updateProductLinks(
        $storeId,
        $target,
        $storeKeeperEndpoint = 'getUpsellShopProductIds',
        $linkType = 'upsell'
    )
    {
        $storekeeperProductId = $target->getStorekeeperProductId();
        if (empty($storeKeeperProductId)) {
            $storekeeperProductId = $target->getData('storekeeper_product_id');
        }

        if (empty($storekeeperProductId)) {
            throw new \Exception("Missing 'storekeeper_product_id' for {$target->getSku()}");
        }
        $storekeeperLinkIds = $this->orderApiClient->$storeKeeperEndpoint($storeId, $storekeeperProductId);

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

    /**
     * If product exist
     *
     * @param $storeId
     * @param array $result
     * @return false|\Magento\Catalog\Api\Data\ProductInterface|\Magento\Framework\DataObject
     */
    public function exists($storeId, array $result)
    {
        $storekeeper_id = $this->getResultStoreKeeperId($result);
        $collection = $this->productCollectionFactory->create();
        $collection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('storekeeper_product_id', $storekeeper_id)
            ->setFlag('has_stock_status_filter', false);

        if (is_array($result) && isset($result['flat_product']) && isset($result['flat_product']['product'])) {
            $collection->addAttributeToFilter(
                'sku', $result['flat_product']['product']['sku'] ?? null
            );
        }

        if ($collection->count()) {
            $firstItem = $collection->getFirstItem();
            return $firstItem;
        }

        try {
            $storekeeper_sku = $this->getResultSku($result);

            if (!is_null($storekeeper_sku)) {
                return $this->productRepository->get($storekeeper_sku);
            }
        } catch (\Exception $e) {
            // ignoring
        }

        return false;
    }

    /**
     * If categories exist
     *
     * @param $storekeeper_category_ids
     * @return false|\Magento\Framework\DataObject[]|void
     * @throws LocalizedException
     */
    public function categoriesExist($storekeeper_category_ids)
    {
        if (count($storekeeper_category_ids) > 0) {
            $collection = $this->categoryCollectionFactory->create();
            $collection
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('storekeeper_category_id', ['in', $storekeeper_category_ids])
                ->setFlag('has_stock_status_filter', false);

            if ($collection->count() > 0) {
                return $collection->getItems();
            }

            return false;
        }
    }

    /**
     * Get Store WebsiteId
     *
     * @param $storeId
     * @return int|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getStoreWebsiteId($storeId)
    {
        if (!isset($this->websiteIds[$storeId])) {
            $store = $this->storeManager->getStore($storeId);
            $this->websiteIds[$storeId] = $store->getWebsiteId();
        }

        return $this->websiteIds[$storeId];
    }

    /**
     * On product create
     *
     * @param $storeId
     * @param array $result
     * @return string|null
     */
    public function onCreate($storeId, array $result)
    {
        return $this->update($storeId, null, $result);
    }

    /**
     * On product deactivate
     *
     * @param $storeId
     * @param $targetId
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
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

    /**
     * On product activate
     *
     * @param $storeId
     * @param $targetId
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
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

    /**
     * Update product
     *
     * @param $storeId
     * @param $target
     * @param array $result
     * @return string|void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function update($storeId, $target = null, array $result = [])
    {
        $this->storeManager->setCurrentStore($storeId);
        $language = $this->authHelper->getLanguageForStore($storeId);
        $websiteId = $this->getStoreWebsiteId($storeId);

        $flat_product = $result['flat_product'];
        $product = $flat_product['product'];
        $type = $product['type'];

        if ($type == 'simple' || $type == 'configurable_assign') {
            $target = $this->processProduct($storeId, $result, $flat_product, $product, $target, $language);
        } elseif ($type == 'configurable') {
            $response = $this->orderApiClient->getConfigurableShopProductOptions(
                $language,
                $storeId,
                $result['id']
            );

            if (array_key_exists('configurable_associated_shop_products', $response)) {
                $associatedShopProducts = $response['configurable_associated_shop_products'];
                $this->_simpleProductIds = [];

                foreach ($associatedShopProducts as $associatedShopProduct) {
                    $storeKeeperId = $associatedShopProduct['shop_product_id'];
                    $this->updateById($storeId, $storeKeeperId);
                }

                $target = $this->processProduct($storeId, $result, $flat_product, $product, $target, $language);

                if (array_key_exists('attributes', $response)) {
                    $configurableAttributesData = $this->getConfigurableAttributesData($response['attributes']);
                    $configurableOptions = $this->optionsFactory->create($configurableAttributesData);

                    $extensionConfigurableAttributes = $target->getExtensionAttributes();
                    $extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
                    $extensionConfigurableAttributes->setConfigurableProductLinks($this->_simpleProductIds);

                    $target->setExtensionAttributes($extensionConfigurableAttributes);

                    $this->productRepository->save($target);
                }
            }
        } else {
            return "Skipping product type {$type} with sku {$sku}\n";
        }
    }

    /**
     * @param array $sourceItemData
     * @param array $result
     * @param array $product
     * @return array
     */
    public function updateSourceItemStock(array $sourceItemData, array $result, array $product): array
    {
        $product_stock_value = (array_key_exists('orderable_stock_value', $result)) ?
            $result['orderable_stock_value'] :
            null;
        $product_stock_unlimited = $product['product_stock']['unlimited'];
        $backorder_enabled = (array_key_exists('backorder_enabled', $result)) ? $result['backorder_enabled'] : null;
        $in_stock = $this->getInStock($result);

        if ($product_stock_unlimited === true && $in_stock) {
            $sourceItemData['manage_stock'] = 0;
        } elseif ($product_stock_unlimited === true && !$in_stock) {
            $sourceItemData['manage_stock'] = 1;
        } else {
            $sourceItemData['manage_stock'] = 1;
        }

        if ($backorder_enabled === true) {
            $sourceItemData['backorders'] = true;
            $sourceItemData['use_config_backorders'] = false;
        } elseif ($backorder_enabled === false) {
            $sourceItemData['backorders'] = false;
            $sourceItemData['use_config_backorders'] = false;
        } else {
            $sourceItemData['use_config_backorders'] = true;
        }

        $stock_quantity = $sourceItemData['manage_stock'] ? $product_stock_value : null;

        if (!is_null($stock_quantity) && $stock_quantity < 0) {
            $stock_quantity = 0;
        }

        $sourceItemData['quantity'] = $stock_quantity;

        return $sourceItemData;
    }

    /**
     * @param array $result
     * @return bool
     */
    public function getInStock(array $result): bool
    {
        $product_stock_value = (array_key_exists('orderable_stock_value', $result)) ?
            $result['orderable_stock_value'] :
            null;

        return $in_stock = null === $product_stock_value || $product_stock_value > 0;
    }

    /**
     * Set gallery image
     *
     * @param string $imageUrl
     * @param Product $target
     * @param bool $mainImage
     * @return bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */

    private function setGalleryImage(string $imageUrl, Product $target, bool $mainImage): bool
    {
        $tmpDir = $this->getMediaTmpDir();
        $newImage = $tmpDir . $this->getImageName($imageUrl);
        $result = $this->file->read($imageUrl, $newImage);
        $imageTypes = [];

        if ($mainImage) {
            $imageTypes = ['image', 'small_image', 'thumbnail', 'swatch_image'];
        }

        if ($result) {
            try {
                $target->addImageToMediaGallery($newImage, $imageTypes, true, false);

                return true;
            } catch (LocalizedException $e) {
                $this->logger->error($exception->getMessage(), $this->logger->buildReportData($exception));
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param string $imageUrl
     * @return string
     */
    private function getImageName(string $imageUrl): string
    {
        $path = parse_url($imageUrl, PHP_URL_PATH);

        return basename($path);
    }

    /**
     * Get media temp directory path
     *
     * @return string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getMediaTmpDir()
    {
        $tmpDir = $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'import/';
        $this->file->checkAndCreateFolder($tmpDir);

        return $tmpDir;
    }

    /**
     * Get result StoreKeeperId
     *
     * @param $result
     * @return mixed
     */
    private function getResultStoreKeeperId($result)
    {
        return $result['product_id'];
    }

    /**
     * Get result sku
     *
     * @param $result
     * @return mixed
     */
    private function getResultSku($result)
    {
        if (array_key_exists('flat_product', $result)) {
            if (array_key_exists('product', $result['flat_product'])) {
                if (array_key_exists('sku', $result['flat_product']['product'])) {
                    return $result['flat_product']['product']['sku'];
                }
            }
        }

        return null;
    }


    /**
     * Get result category Ids
     *
     * @param $result
     * @return array
     */
    private function getResultCategoryIds($result)
    {
        return array_map(function ($cat) {
            return $cat['id'];
        }, $result['flat_product']['categories'] ?? []);
    }

    /**
     * Set product to use Default Values
     *
     * @param $target
     * @param $storeId
     * @return void
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function setProductToUseDefaultValues($target, $storeId)
    {
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

    /**
     * @return void
     */
    public function cleanProductStorekeeperId(string $storeId): void
    {
        try {
            $productCollection = $this->productCollectionFactory->create();
            $productCollection
                ->setStoreId($storeId)
                ->addAttributeToFilter('storekeeper_product_id', ['neq' => NULL])
                ->setFlag('has_stock_status_filter', false);
            $productCollectionIds = $productCollection->getAllIds();

            if (count($productCollectionIds) > 0) {
                $this->productAction->updateAttributes(
                    $productCollectionIds,
                    ['storekeeper_product_id' => NULL],
                    $storeId
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'error while cleanProductStorekeeperId',
                ['error' =>$this->logger->buildReportData($e), 'storeId' => $storeId]
            );
        }
    }

    /**
     * @param string $storeId
     * @param array $result
     * @param array $flat_product
     * @param array $product
     * @param $target
     * @param $language
     * @return Product
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    private function processProduct(
        string $storeId, array $result, array $flat_product, array $product, $target, $language
    ): Product
    {
        $shouldUpdate = false;
        if ($this->configHelper->isCatalogPricesIncludesTax($storeId)) {
            $productPrice = $result['product_price']['ppu_wt'];
            $productDefaultPrice = $result['product_default_price']['ppu_wt'];
        } else {
            $productPrice = $result['product_price']['ppu'];
            $productDefaultPrice = $result['product_default_price']['ppu'];
        }

        $title = $flat_product['title'];
        $summary = $flat_product['summary'] ?? '';
        $body = $flat_product['body'];
        $slug = $flat_product['slug'];
        $sku = $product['sku'];
        $type = $product['type'];
        $catalogEntity = $this->entityTypeFactory->create()->loadByCode(Product::ENTITY);
        $catalogEntityId = $catalogEntity->getId();

        $update = !is_null($target);
        $create = !$update;

        if ($update) {
            $target = $this->productRepository->getById($target->getId());
        } else {
            $shouldUpdate = true;
            $target = $this->productFactory->create();
        }

        if ($language == " ") {
            $target->setStoreId(Store::DEFAULT_STORE_ID);
        }

        $newStatus = $product['active'] ? Status::STATUS_ENABLED : Status::STATUS_DISABLED;

        if ($target->getStatus() != $newStatus) {
            $target->setStatus($newStatus);
        }

        if ($create) {
            $target->setSku($sku);

            if ($type == 'configurable') {
                $target->setVisibility(Visibility::VISIBILITY_BOTH);
                $target->setTypeId('configurable');
            }
        }

        if ($target->getName() != $title) {
            $shouldUpdate = true;
            $target->setName($title);
        }

        if ((float) $target->getPrice() != (float) $productDefaultPrice) {
            $shouldUpdate = true;
            $target->setPrice($productDefaultPrice);
        }

        if ($productPrice != $productDefaultPrice) {
            if ((float) $target->getSpecialPrice() != (float) $productPrice) {
                $shouldUpdate = true;
                $target->setSpecialPrice($productPrice);
            }
        }

        $storekeeper_id = $this->getResultStoreKeeperId($result);

        if ($target->getStorekeeperProductId() != $storekeeper_id) {
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
        $parseDown->setSafeMode(true);

        $newDescription = $parseDown->text($body);
        if (
            $target->getDescription() != $newDescription
            && !$this->productDescription->isDisallowedContentExist($target->getDescription())
        ) {
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

        $target = $this->processImages($flat_product, $target, $shouldUpdate);

        if ($this->updateProductLinks($storeId, $target, 'getUpsellShopProductIds', 'upsell')) {
            $shouldUpdate = true;
        }

        if ($this->updateProductLinks($storeId, $target,'getCrossSellShopProductIds', 'crosssell')) {
            $shouldUpdate = true;
        }

        /**
         * Load/create attribute set in Magento basned on SK attributes et name
         * If it differs from currentl product attribute set - assign it
         */
        if (array_key_exists('attribute_set_name', $flat_product)) {
            $attributeSetName = $flat_product['attribute_set_name'];
            $attributeSetId = $this->attributes->getAttributeSetIdByName($catalogEntityId, $attributeSetName);

            if ($target->getAttributeSetId() != $attributeSetId) {
                $target->setAttributeSetId($attributeSetId);
                $shouldUpdate = true;
            }
        } else {
            $attributeSetId = $target->getAttributeSetId();
        }

        if ($shouldUpdate) {
            $this->productRepository->save($target);

            if ($update) {
                if ($language == ' ') {
                    $this->setProductToUseDefaultValues($target, $storeId);
                }
            }
        }

        /**
         * Process custom attributes AFTER product save, in case of changed attribute set
         */
        if (array_key_exists('content_vars', $flat_product)) {
            $target = $this->attributes->processProductAttributes(
                $flat_product,
                $target,
                $storeId,
                $catalogEntityId,
                $attributeSetId
            );
        }

        $this->processStock($result, $product, $sku);
        $this->assignCategories($target, $result);

        if ($type == 'configurable_assign') {
            $this->_simpleProductIds[] = $target->getId();
        }

        return $target;
    }

    /**
     * @param array $flat_product
     * @param Product $target
     * @param bool $shouldUpdate
     * @return Product
     */
    private function processImages(array $flat_product, Product $target, bool $shouldUpdate): Product
    {
        $galleryImages = $target->getMediaGalleryEntries();
        $existingImagesArray = [];
        foreach ($galleryImages as $entryId => $image) {
            $mediaGalleryImage = $target->getMediaGalleryImages()->getItemById($image->getId());
            if ($mediaGalleryImage) {
                $skImageId = $mediaGalleryImage->getStorekeeperImageId();
                $skImageIds = array_column($flat_product['product_images'], 'id');
                /**
                 * Look for storekeeper image id of current gallery image in array of images from SK backoffice
                 * if current product does not match id or id is missing - remove gallery image
                 */
                if (
                    !in_array($skImageId, $skImageIds)
                    && $this->productExportManager->isImageFormatAllowed($mediaGalleryImage->getPath())
                ) {
                    $shouldUpdate = true;
                } else {
                    $existingImagesArray[$skImageId] = $image;
                }
            }
        }

        if ($shouldUpdate) {
            $target->setMediaGalleryEntries($existingImagesArray);
        }

        if (isset($flat_product['product_images'])) {
            foreach ($flat_product['product_images'] as $product_image) {
                $imageId = $product_image['id'];

                if (!isset($existingImagesArray[$imageId])) {
                    $mainImage = false;
                    // If current image matches id with main image - assign it as main image for magento
                    if (isset($flat_product['main_image']) && $flat_product['main_image']['id'] == $imageId) {
                        $mainImage = true;
                    }
                    $shouldUpdate = $this->setGalleryImage($product_image['big_url'], $target, $mainImage)
                        || $shouldUpdate;
                    if ($shouldUpdate) {
                        /**
                         * Save product via repository fo any given image
                         * Otherwise new gallery item will not be available, we only get the id after saving to storage
                         */
                        $this->productRepository->save($target);
                        $shouldUpdate = false;
                    }
                    $target = $this->productRepository->get($target->getSku());
                    /**
                     * Load product item with latest id
                     * because framework does not allow to receive gallery item via addImageToMediaGallery() call
                     */
                    $galleryImage = $target->getMediaGalleryImages()->getLastItem();
                    // Assign SK image id for newly created M2 gallery image
                    $this->setStorekeeperImageId($galleryImage->getId(), $imageId);
                }
            }
        }

        if ($shouldUpdate) {
            $this->productRepository->save($target);
        }

        return $target;
    }

    /**
     * @param Product $target
     * @param array $result
     * @return void
     * @throws LocalizedException
     */
    private function assignCategories(Product $target, array $result): void
    {
        // assign the store to the first available, otherwise delete operations will go wrong
        $categoryIds = $this->getResultCategoryIds($result);
        if (!empty($categoryIds)) {
            // check if categories exist
            if ($categories = $this->categoriesExist($categoryIds)) {
                $categoryIds = array_map(function ($category) {
                    return $category->getId();
                }, $categories);

                $diff = array_diff($target->getCategoryIds(), $categoryIds);
                if (empty($target->getCategoryIds()) || $diff) {
                    if ($diff) {
                        foreach ($diff as $categoryId) {
                            $this->categoryLinkRepository->deleteByIds($categoryId, $target->getSku());
                        }
                    }

                    $this->categoryLinkManagement->assignProductToCategories(
                        $target->getSku(),
                        $categoryIds
                    );
                }
            }
        }
    }

    /**
     * @param array $result
     * @param array $product
     * @param string $sku
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     */
    private function processStock(array $result, array $product, string $sku): void
    {
        $sourceItemData = [
            'source_code' => $this->configHelper->getStockSource(),
            'status' => 1
        ];

        $sourceItemData = $this->updateSourceItemStock($sourceItemData, $result, $product);

        $this->sourceItemsProcessor->execute($sku, [$sourceItemData]);
    }

    private function getConfigurableAttributesData(array $attributes): array
    {
        $configurableAttributesData = [];
        $position = 0;
        foreach ($attributes as $attribute) {
            $attributeValues = [];
            $attributeCode = str_replace('-', '_', $attribute['name']);
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
            $attributeLabel = $attribute->getData('frontend_label');
            $attributeOptions = $attribute->getOptions();

            foreach ($attributeOptions as $attributeOption) {
                $attributeValues[] = [
                    'label' => $attributeLabel,
                    'attribute_id' => $attribute->getId(),
                    'value_index' => $attributeOption->getValue()
                ];
            }

            $configurableAttributesData[] = [
                'attribute_id' => $attribute->getId(),
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getStoreLabel(),
                'position' => $position,
                'values' => $attributeValues,
            ];
            $position++;
        }

        return $configurableAttributesData;
    }

    /**
     * @param string $galleryImageId
     * @param int $skImageId
     * @return void
     */
    private function setStorekeeperImageId(string $galleryImageId, int $skImageId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(
            \Magento\Catalog\Model\ResourceModel\Product\Gallery::GALLERY_TABLE
        );
        $data = ['storekeeper_image_id' => $skImageId];
        $where = ['value_id = ?' => $galleryImageId];
        $connection->update($tableName, $data, $where);
    }
}
