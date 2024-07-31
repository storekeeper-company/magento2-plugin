<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
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
use Magento\Framework\App\ResourceConnection;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Base36Coder;
use StoreKeeper\StoreKeeper\Helper\Config;
use StoreKeeper\StoreKeeper\Helper\ProductDescription as ProductDescriptionHelper;
use StoreKeeper\StoreKeeper\Model\Config\Source\Product\Attributes;
use StoreKeeper\StoreKeeper\Logger\Logger;
use Symfony\Component\Mime\FileinfoMimeTypeGuesser;

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
        "created_at", //no label
        "custom_design",
        "custom_design_from",
        "custom_design_to",
        "custom_layout",
        "custom_layout_update",
        "custom_layout_update_file",
        "description",
        "gallery",
        "gift_message_available",
        "has_options", //no label
        "image",
        "image_label",
        "links_exist", //no label
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
        "old_id", //no label
        "options_container",
        "page_layout",
        "price",
        "price_type",
        "price_view",
        "quantity_and_stock_status",
        "required_options", //no label
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
        "updated_at", //no label
        "featured",
        "minimal_price",
        "msrp",
        "swatch_image",
        "thumbnail",
        "tier_price",
        "url_key",
        "visibility",
        "weight",
        "dynamic_weight"

        //need to add url_path? it doesnt have fr label too
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
    private Logger $logger;
    private AttributeCollectionFactory $attributeCollectionFactory;
    private Base36Coder $base36Coder;
    private FileinfoMimeTypeGuesser $fileinfoMimeTypeGuesser;
    private ResourceConnection $resourceConnection;
    private ProductDescriptionHelper $productDescription;
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
     * @param Logger $logger
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param Base36Coder $base36Coder
     * @param FileinfoMimeTypeGuesser $fileinfoMimeTypeGuesser
     * @param ResourceConnection $resourceConnection
     * @param ProductDescriptionHelper $productDescription
     */
    public function __construct(
        Resolver $localeResolver,
        CollectionFactory $productCollectionFactory,
        Csv $csv,
        Filesystem $filesystem,
        DirectoryList $directoryList,
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
        Logger $logger,
        AttributeCollectionFactory $attributeCollectionFactory,
        Base36Coder $base36Coder,
        FileinfoMimeTypeGuesser $fileinfoMimeTypeGuesser,
        ResourceConnection $resourceConnection,
        ProductDescriptionHelper $productDescription
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
        $this->productCollectionFactory = $productCollectionFactory;
        $this->csv = $csv;
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;
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
        $this->base36Coder = $base36Coder;
        $this->fileinfoMimeTypeGuesser = $fileinfoMimeTypeGuesser;
        $this->resourceConnection = $resourceConnection;
        $this->productDescription = $productDescription;
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
            $descriptionFormatted = $this->productDescription->formatProductDescription($product->getDescription());

            $data = [
                $productData['product_type'], // path://product.type
                $product->getSku(), // path://product.sku
                $product->getName(), // path://title
                $product->getShortDescription(), // path://summary
                $descriptionFormatted, // path://body
                $product->getUrlKey(), // path://slug
                $product->getMetaTitle(), // path://seo_title
                $product->getMetaKeyword(), // path://seo_keywords
                $product->getMetaDescription(), // path://seo_description
                $productData['is_active'], // path://product.active
                $stockData['is_in_stock'], // path://product.product_stock.in_stock
                $stockData['stock_qty'], // path://product.product_stock.value
                'no', // path://product.product_stock.unlimited
                $stockData['is_backorder_enabled'], // path://shop_products.main.backorder_enabled
                $taxData['vat_rate'] ?? null, // path://product.product_price.tax
                $taxData['vat_iso2'] ?? null, // path://product.product_price.tax_rate.country_iso2
                $this->storeManager->getStore()->getCurrentCurrencyCode(), // path://product.product_price.currency_iso3
                $this->productHelper->getTaxPrice($product, $productPrice, false),// path://product.product_price.ppu - price excl. tax
                $this->productHelper->getTaxPrice($product, $productPrice, true), // path://product.product_price.ppu_wt - price incl. tax
                $this->productHelper->getTaxPrice($product, $productSpecialPrice, false), // path://product.product_discount_price.ppu - Discount price
                $this->productHelper->getTaxPrice($product, $productSpecialPrice, true), // path://product.product_discount_price.ppu_wt - Discount price with VAT
                null, // path://product.product_purchase_price.ppu - Purchase price
                null, // path://product.product_purchase_price.ppu_wt - Purchase price with VAT
                null, // path://product.product_bottom_price.ppu - Bottom price
                null, // path://product.product_bottom_price.ppu_wt - Bottom price with VAT
                $this->productHelper->getTaxPrice($product, $productCostPrice, false), // path://product.product_cost_price.ppu - Cost price
                $this->productHelper->getTaxPrice($product, $productCostPrice, true), // path://product.product_cost_price.ppu_wt - Cost price with VAT
                null, // path://product.configurable_product_kind.alias - Product kind alias
                null, // path://product.configurable_product.sku - Configurable product sku
                $categoryData['category_name'] ?? null, // path://main_category.title
                $categoryData['category_url'] ?? null, // path://main_category.slug
                null, // path://extra_category_slugs - Extra Category slugs
                null, // path://extra_label_slugs - Extra Label slugs
                $productData['attribute_set_name'], // path://attribute_set_name
                $this->formatAlias($productData['attribute_set_name']), // path://attribute_set_alias - Attribute set alias
                $productData['is_salable'], // path://shop_products.main.active
                null // path://shop_products.main.relation_limited - Sales relation limited
            ];
            $data = $this->addProductImageUrlData($data, $product);
            $result[] = array_combine(self::HEADERS_PATHS, $data);
            $dataKey = array_key_last($result);
            if (is_array($featuredAttributes)) {
                foreach ($featuredAttributes as $key => $value) {
                    if (
                        $value !== Attributes::NOT_MAPPED || ($value === Attributes::NOT_MAPPED && $key === 'barcode')
                    ) {
                        $value = $value === Attributes::NOT_MAPPED ? 'sku' : $value;
                        $attributeValue = $product->getData($value);
                        //Get Label of mapped attribute from configs
                        $attributeLabel = $this->convertToLabel($key);

                        if ($key == 'barcode') {
                            $attributeValue = $this->validateBarcode($attributeValue);
                        }

                        $keyEncoded = $this->base36Coder->encode($key);
                        $attribute = $product->getResource()->getAttribute($value);

                        if ($attributeValue !== null && $attribute->usesSource()) {
                            $attributeValue = $attribute->getFrontend()->getValue($product);
                        }

                        $result = $this->fillAttributeRow(
                            $value,
                            $keyEncoded,
                            $attributeValue,
                            $product,
                            $result,
                            $dataKey
                        );

                        $this->extendHeaderLabels($attributeLabel . ' (raw)');
                        $this->extendHeaderLabels($attributeLabel . ' (label)');
                        $this->extendDisallowedAttributes($value);
                    }
                }
            }

            foreach ($productAttributes as $productAttribute) {
                $attributeCode = $productAttribute->getAttributeCode();
                if (array_search($attributeCode, $this->getDisallowedAttributesExtended()) === false) {
                    $attributeValue = $product->getData($attributeCode);
                    $attributeCodeEncoded = $this->base36Coder->encode($attributeCode);
                    if ($attributeValue !== null && $productAttribute->usesSource()) {
                        $attributeValue = $productAttribute->getFrontend()->getValue($product);
                    }

                    $result = $this->fillAttributeRow(
                        $attributeCode,
                        $attributeCodeEncoded,
                        $attributeValue,
                        $product,
                        $result,
                        $dataKey
                    );
                    $this->extendHeaderLabels($productAttribute->getDefaultFrontendLabel() . ' (raw)');
                    $this->extendHeaderLabels($productAttribute->getDefaultFrontendLabel() . ' (label)');
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
            if ($this->isImageFormatAllowed($productImage->getPath())) {
                $data[] = $productImage->getUrl();
            }
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
        $taxCountryId = $this->configHelper->getDefaultCountry();
        $taxRate = $this->getTaxRate($product->getTaxClassId(), $taxCountryId);
        if ($taxRate) {
            $data = [
                'vat_rate' => (float)($taxRate/100),
                'vat_iso2' => $taxCountryId
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

    /**
     * @param string|null $url
     * @return bool
     */
    public function isImageFormatAllowed(?string $url): bool
    {
        $imageType = $this->fileinfoMimeTypeGuesser->guessMimeType($url);

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        return in_array($imageType, $allowedTypes);
    }

    /**
     * @param $attributeCode
     * @param $attributeCodeEncoded
     * @param $attributeValue
     * @param $product
     * @param $result
     * @param $dataKey
     * @return array
     */
    private function fillAttributeRow($attributeCode, $attributeCodeEncoded, $attributeValue, $product, $result, $dataKey)
    {
        if ($attributeValue !== null) {
            if ($attributeValue instanceof \Magento\Framework\Phrase) {
                $result[$dataKey]['path://content_vars.encoded__' . $attributeCodeEncoded . '.value_label'] = $attributeValue->getText();
            } else {
                $result[$dataKey]['path://content_vars.encoded__' . $attributeCodeEncoded . '.value_label'] = $attributeValue;
            }

            $result[$dataKey]['path://content_vars.encoded__' . $attributeCodeEncoded . '.value'] = $product->getData($attributeCode);
        }

        $this->extendHeaderPaths('path://content_vars.encoded__' . $attributeCodeEncoded . '.value');
        $this->extendHeaderPaths('path://content_vars.encoded__' . $attributeCodeEncoded . '.value_label');

        return $result;
    }

    /**
     * @param $string
     * @return string
     */
    private function convertToLabel($string): string
    {
        $string = str_replace('_', ' ', $string);
        $string = ucwords($string);

        return $string;
    }

    /**
     * @param string $attributeValue
     * @return string|null
     */
    private function validateBarcode(string $attributeValue): ?string
    {
        try {
            $data = str_pad($attributeValue, 13, '0', STR_PAD_LEFT);
            $barcode = new \Picqer\Barcode\Types\TypeEan13();
            $barcode = $barcode->getBarcodeData($data);
        } catch (\Throwable $e) {
            $attributeValue = null;
        }

        return $attributeValue;
    }

    /**
     * @param $productTaxClassId
     * @param $taxCountryId
     * @return mixed
     */
    private function getTaxRate($productTaxClassId, $taxCountryId)
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(['tc' => 'tax_calculation'], [])
            ->join(
                ['tcr' => 'tax_calculation_rate'],
                'tc.tax_calculation_rate_id = tcr.tax_calculation_rate_id',
                ['rate']
            )
            ->join(
                ['tcrl' => 'tax_calculation_rule'],
                'tc.tax_calculation_rule_id = tcrl.tax_calculation_rule_id',
                []
            )
            ->join(
                ['tcc' => 'tax_class'],
                'tc.product_tax_class_id = tcc.class_id',
                []
            )
            ->where('tc.product_tax_class_id = ?', $productTaxClassId)
            ->where('tcr.tax_country_id = ?', $taxCountryId);

        $rate = $connection->fetchOne($select);

        return $rate;
    }
}
