<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Calculation as ResourceCalculation;
use Magento\Tax\Api\TaxClassRepositoryInterface;
use Magento\Tax\Model\ResourceModel\Calculation\Rate\CollectionFactory as RateCollectionFactory;
use StoreKeeper\StoreKeeper\Model\ResourceModel\TaxCalculation\CollectionFactory as TaxCalculationCollectionFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;
use Magento\Catalog\Helper\Data as ProductHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Catalog\Helper\ImageFactory;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use Psr\Log\LoggerInterface;

class ProductExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://product.type',
        'path://product.sku',
        'path://title',
        'path://summary',
        'path://body',
        'path://slug',
        'path://seo_title',
        'path://seo_keywords',
        'path://seo_description',
        'path://product.active',
        'path://product.product_stock.in_stock',
        'path://product.product_stock.value',
        'path://product.product_stock.unlimited',
        'path://shop_products.main.backorder_enabled',
        'path://product.product_price.tax',
        'path://product.product_price.tax_rate.country_iso2',
        'path://product.product_price.currency_iso3',
        'path://product.product_price.ppu',
        'path://product.product_price.ppu_wt',
        'path://product.product_discount_price.ppu',
        'path://product.product_discount_price.ppu_wt',
        'path://product.product_purchase_price.ppu',
        'path://product.product_purchase_price.ppu_wt',
        'path://product.product_bottom_price.ppu',
        'path://product.product_bottom_price.ppu_wt',
        'path://product.product_cost_price.ppu',
        'path://product.product_cost_price.ppu_wt',
        'path://product.configurable_product_kind.alias',
        'path://product.configurable_product.sku',
        'path://main_category.title',
        'path://main_category.slug',
        'path://extra_category_slugs',
        'path://extra_label_slugs',
        'path://attribute_set_name',
        'path://attribute_set_alias',
        'path://shop_products.main.active',
        'path://shop_products.main.relation_limited',
        'path://product.product_images.0.download_url',
        'path://product.product_images.1.download_url',
        'path://product.product_images.2.download_url',
        'path://product.product_images.3.download_url',
        'path://product.product_images.4.download_url',
        'path://product.product_images.5.download_url',
        'path://product.product_images.6.download_url',
        'path://product.product_images.7.download_url',
        'path://product.product_images.8.download_url',
        'path://product.product_images.9.download_url'
    ];
    const HEADERS_LABELS = [
        'Type',
        'Product number',
        'Product name',
        'Short description',
        'Long description',
        'slug',
        'SEO title',
        'SEO keywords',
        'SEO description',
        'Active',
        'In Stock',
        'Stock Value',
        'Always on stock',
        'Backorder enabled',
        'VAT Rate',
        'VAT Rate Country code (iso2)',
        'Currency',
        'Price',
        'Price with VAT',
        'Discount price',
        'Discount price with VAT',
        'Purchase price',
        'Purchase price with VAT',
        'Bottom price',
        'Bottom price with VAT',
        'Cost price',
        'Cost price with VAT',
        'Product kind alias',
        'Configurable product sku',
        'Category',
        'Category slug',
        'Extra Category slugs',
        'Extra Label slugs',
        'Attribute set name',
        'Attribute set alias',
        'Sales active',
        'Sales relation limited',
        'Image 1',
        'Image 2',
        'Image 3',
        'Image 4',
        'Image 5',
        'Image 6',
        'Image 7',
        'Image 8',
        'Image 9',
        'Image 10'
    ];

    const DISALLOWED_ATTRIBUTES = [
        "category_ids",
        "cost",
        "created_at",
        "custom_design",
        "custom_design_from",
        "custom_design_to",
        "custom_layout",
        "custom_layout_update",
        "custom_layout_update_file",
        "description",
        "gallery",
        "gift_message_available",
        "has_options",
        "image",
        "image_label",
        "links_exist",
        "links_purchased_separately",
        "links_title",
        "media_gallery",
        "meta_description",
        "meta_keyword",
        "meta_title",
        "msrp_display_actual_price_type",
        "name",
        "news_from_date",
        "news_to_date",
        "old_id",
        "options_container",
        "page_layout",
        "price",
        "price_type",
        "price_view",
        "quantity_and_stock_status",
        "required_options",
        "samples_title",
        "shipment_type",
        "short_description",
        "sku",
        "sku_type",
        "small_image",
        "small_image_label",
        "special_from_date",
        "special_price",
        "special_to_date",
        "status",
        "tax_class_id",
        "updated_at"
    ];

    private CollectionFactory $productCollectionFactory;
    private Csv $csv;
    private Filesystem $filesystem;
    private DirectoryList $directoryList;
    private File $file;
    private StoreManagerInterface $storeManager;
    private StockRegistryInterface $stockRegistry;
    private TaxCalculationInterface $taxCalculation;
    private Calculation $calculation;
    private TaxClassRepositoryInterface $taxClassRepository;
    private RateCollectionFactory $rateCollectionFactory;
    private ResourceCalculation $resourceCalculation;
    private TaxCalculationCollectionFactory $taxCalculationCollectionFactory;
    private TaxRateRepositoryInterface $taxRateRepository;
    private ProductHelper $productHelper;
    private CategoryRepositoryInterface $categoryRepository;
    private SetFactory $attributeSetFactory;
    private ImageFactory $imageFactory;
    private Auth $authHelper;
    private Config $configHelper;
    private LoggerInterface $logger;
    private AttributeCollectionFactory $attributeCollectionFactory;
    protected array $headerPathsExtended = self::HEADERS_PATHS;
    protected array $headerLabelsExtended = self::HEADERS_LABELS;
    protected array $disallowedAttributesExtended = self::DISALLOWED_ATTRIBUTES;

    /**
     * ExportManager constructor
     *
     * @param Resolver $localeResolver
     * @param CollectionFactory $productCollectionFactory
     * @param Csv $csv
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param File $file
     * @param StoreManagerInterface $storeManager
     * @param StoreConfigManagerInterface $storeConfigManager
     * @param StockRegistryInterface $stockRegistry
     * @param TaxCalculationInterface $taxCalculation
     * @param Calculation $calculation
     * @param TaxClassRepositoryInterface $taxClassRepository
     * @param RateCollectionFactory $rateCollectionFactory
     * @param ResourceCalculation $resourceCalculation
     * @param TaxCalculationCollectionFactory $taxCalculationCollectionFactory
     * @param TaxRateRepositoryInterface $taxRateRepository
     * @param ProductHelper $productHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param SetFactory $attributeSetFactory
     * @param ImageFactory $imageFactory
     * @param Auth $authHelper
     * @param Config $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Resolver $localeResolver,
        CollectionFactory $productCollectionFactory,
        Csv $csv,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        File $file,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        StockRegistryInterface $stockRegistry,
        TaxCalculationInterface $taxCalculation,
        Calculation $calculation,
        TaxClassRepositoryInterface $taxClassRepository,
        RateCollectionFactory $rateCollectionFactory,
        ResourceCalculation $resourceCalculation,
        TaxCalculationCollectionFactory $taxCalculationCollectionFactory,
        TaxRateRepositoryInterface $taxRateRepository,
        ProductHelper $productHelper,
        CategoryRepositoryInterface $categoryRepository,
        SetFactory $attributeSetFactory,
        ImageFactory $imageFactory,
        Auth $authHelper,
        Config $configHelper,
        LoggerInterface $logger,
        AttributeCollectionFactory $attributeCollectionFactory
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->csv = $csv;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->file = $file;
        $this->storeManager = $storeManager;
        $this->stockRegistry = $stockRegistry;
        $this->taxCalculation = $taxCalculation;
        $this->calculation = $calculation;
        $this->taxClassRepository = $taxClassRepository;
        $this->rateCollectionFactory = $rateCollectionFactory;
        $this->resourceCalculation = $resourceCalculation;
        $this->taxCalculationCollectionFactory = $taxCalculationCollectionFactory;
        $this->taxRateRepository = $taxRateRepository;
        $this->productHelper = $productHelper;
        $this->categoryRepository = $categoryRepository;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->imageFactory = $imageFactory;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    public function getProductExportData(array $products): array
    {
        $result = [];
        $featuredAttributes = $this->configHelper->getFeaturedAttributesMapping();
        $productAttributes = $this->attributeCollectionFactory->create();
        foreach ($products as $product) {
            /** @var ProductInterface $product */
            $productPrice = $product->getPrice();
            $productSpecialPrice = $product->getSpecialPrice();
            $productCostPrice = $product->getCost();

            $productData = $this->getProductData($product);
            $stockData = $this->getStockData($product);
            $taxData = $this->getTaxData($product);
            $categoryData = $this->getCategoryData($product);

            $data = [
                $productData['product_type'],
                $product->getSku(),
                $product->getName(),
                $product->getShortDescription(),
                $product->getDescription(),
                $product->getUrlKey(), // slug
                $product->getMetaTitle(),
                $product->getMetaKeyword(),
                $product->getMetaDescription(),
                $productData['is_active'],
                $stockData['is_in_stock'],
                $stockData['stock_qty'],
                $stockData['is_manage_stock'],
                $stockData['is_backorder_enabled'],
                $taxData['vat_rate'] ?? null,
                $taxData['vat_iso2'] ?? null,
                $this->storeManager->getStore()->getCurrentCurrencyCode(),
                $this->productHelper->getTaxPrice($product, $productPrice, false), // price excl. tax
                $this->productHelper->getTaxPrice($product, $productPrice, true), // price incl. tax
                $this->productHelper->getTaxPrice($product, $productSpecialPrice, false), // product.product_discount_price.ppu - Discount price
                $this->productHelper->getTaxPrice($product, $productSpecialPrice, true), // product.product_discount_price.ppu_wt - Discount price with VAT
                null, // product.product_purchase_price.ppu - Purchase price
                null, // product.product_purchase_price.ppu_wt - Purchase price with VAT
                null, // product.product_bottom_price.ppu - Bottom price
                null, // product.product_bottom_price.ppu_wt - Bottom price with VAT
                $this->productHelper->getTaxPrice($product, $productCostPrice, false), // product.product_cost_price.ppu - Cost price
                $this->productHelper->getTaxPrice($product, $productCostPrice, true), // product.product_cost_price.ppu_wt - Cost price with VAT
                null, // product.configurable_product_kind.alias - Product kind alias
                null, // product.configurable_product.sku - Configurable product sku
                $categoryData['category_name'] ?? null,
                $categoryData['category_url'] ?? null,
                null, // extra_category_slugs - Extra Category slugs
                null, // extra_label_slugs - Extra Label slugs
                $productData['attribute_set_name'],
                null, // attribute_set_alias - Attribute set alias
                $productData['is_salable'],
                null // shop_products.main.relation_limited - Sales relation limited
            ];
            $data = $this->addProductImageUrlData($data, $product);
            $result[] = array_combine(self::HEADERS_PATHS, $data);
            foreach ($featuredAttributes as $key => $value) {
                if ($value !== 'not-mapped') {
                    $attributeValue = $product->getData($value);
                    try {
                        $dataKey = key($result);
                        $attribute = $product->getResource()->getAttribute($value);
                        if ($attributeValue !== null && $attribute->usesSource()) {
                            $attributeValue = $attribute->getFrontend()->getValue($product);
                        }

                        if ($attributeValue !== null) {
                            $result[$dataKey]['path://content_vars.' . $key . '.value'] = $attributeValue;
                            $result[$dataKey]['path://content_vars.' . $key . '.value_label'] = $attribute->getDefaultFrontendLabel();
                        }

                        $this->extendHeaderPaths('path://content_vars.' . $key . '.value');
                        $this->extendHeaderPaths('path://content_vars.' . $key . '.value_label');
                        $this->extendHeaderLabels($value . ' (raw)');
                        $this->extendHeaderLabels($value . ' (label)');
                        $this->extendDisallowedAttributes($value);
                    } catch (\Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }

            foreach ($productAttributes as $productAttribute) {
                $attributeCode = $productAttribute->getAttributeCode();
                if (array_search($attributeCode, $this->getDisallowedAttributesExtended()) === false) {
                    $attributeValue = $product->getData($attributeCode);
                    if ($attributeValue !== null && $productAttribute->usesSource()) {
                        $attributeValue = $productAttribute->getFrontend()->getValue($product);
                    }
                    if ($attributeValue !== null) {
                        $result[$dataKey]['path://content_vars.' . $attributeCode . '.value'] = $attributeValue;
                        $result[$dataKey]['path://content_vars.' . $attributeCode . '.value_label'] = $productAttribute->getDefaultFrontendLabel();
                    }

                    $this->extendHeaderPaths('path://content_vars.' . $attributeCode . '.value');
                    $this->extendHeaderPaths('path://content_vars.' . $attributeCode . '.value_label');
                    $this->extendHeaderLabels($attributeCode . ' (raw)');
                    $this->extendHeaderLabels($attributeCode . ' (label)');
                }
            }
        }

        return $result;
    }

    public function getHeaderPathsExtended()
    {
        return $this->headerPathsExtended;
    }

    public function getHeaderLabelsExtended()
    {
        return $this->headerLabelsExtended;
    }

    public function getDisallowedAttributesExtended()
    {
        return $this->disallowedAttributesExtended;
    }

    protected function extendHeaderPaths(string $key)
    {
        if (array_search($key, $this->headerPathsExtended) === false) {
            $this->headerPathsExtended[] = $key;
        }
    }

    protected function extendHeaderLabels(string $key)
    {
        if (array_search($key, $this->headerLabelsExtended) === false) {
            $this->headerLabelsExtended[] = $key;
        }
    }

    protected function extendDisallowedAttributes(string $key)
    {
        if (array_search($key, $this->disallowedAttributesExtended) === false) {
            $this->disallowedAttributesExtended[] = $key;
        }
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function getProductData(ProductInterface $product): array
    {
        $productType = $this->getProductType($product);
        $attributeSetName = $this->attributeSetFactory->create()->load($product->getAttributeSetId())->getAttributeSetName();
        $isActive = ($product->getStatus() == 1) ? 'yes' : 'no';
        $isSalable = $product->isSalable() ? 'yes' : 'no';

        return [
            'product_type' => $productType,
            'is_active' => $isActive,
            'attribute_set_name' => $attributeSetName,
            'is_salable' => $isSalable
        ];
    }

    /**
     * @param ProductInterface $product
     * @return string
     */
    private function getProductType(ProductInterface $product): string
    {
        $validProductTypes = ['configurable', 'simple'];
        $productType = $product->getTypeId();

        if (!in_array($productType, $validProductTypes)) {
            $productType = 'simple';
        }

        return $productType;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function getProductImageUrlData(ProductInterface $product): array
    {
        $data = [];
        foreach ($product->getMediaGalleryImages() as $productImage) {
            $data[] = $productImage->getUrl();
        }

        return $data;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function getStockData(ProductInterface $product): array
    {
        $stockItem = $this->stockRegistry->getStockItem(
            $product->getId(),
            $product->getStore()->getWebsiteId()
        );
        $isInStock = $stockItem->getIsInStock() ? 'yes' : 'no';
        $stockQty = (int)$stockItem->getQty();
        $isBackorderEnabled = !$stockItem->getBackorders() ? 'no' : 'yes';
        $isManageStock = $stockItem->getManageStock() ? 'yes' : 'no';

        return [
            'is_in_stock' => $isInStock,
            'stock_qty' => $stockQty,
            'is_backorder_enabled' => $isBackorderEnabled,
            'is_manage_stock' => $isManageStock
        ];
    }

    /**
     * @param ProductInterface $product
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTaxData(ProductInterface $product): array
    {
        $data = [];

        $taxCalculationCollection = $this->taxCalculationCollectionFactory->create();
        $taxCalculationCollection->addFieldToFilter('product_tax_class_id', $product->getTaxClassId());

        if ($taxCalculationCollection->getSize()) {
            $taxCalculation = $taxCalculationCollection->getFirstItem();
            $taxRateId = $taxCalculation->getData('tax_calculation_rate_id');
            $taxRate = $this->taxRateRepository->get($taxRateId);
            $data = [
                'vat_rate' => (float)$taxRate->getRate(),
                'vat_iso2' => $taxRate->getTaxCountryId()
            ];
        }

        return $data;
    }

    /**
     * @param ProductInterface $product
     * @return array
     */
    private function getCategoryData(ProductInterface $product): array
    {
        $data = [];

        if ($product->getCategoryIds() && $categoryId = $product->getCategoryIds()[0]) {
            $category = $this->categoryRepository->get($categoryId);
            $data = [
                'category_name' => $category->getName(),
                'category_url' => $category->getUrlKey()
            ];
        }

        return $data;
    }

    /**
     * @param array $data
     * @param ProductInterface $product
     * @return array
     */
    private function addProductImageUrlData(array $data, ProductInterface $product): array
    {
        $imageUrlData = $this->getProductImageUrlData($product);
        for ($i = 0; $i < 10; $i++) {
            if (isset($imageUrlData[$i])) {
                $data[] = $imageUrlData[$i];
            } else {
                $data[] = null;
            }
        }

        return $data;
    }
}
