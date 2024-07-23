<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Helper\Base36Coder;
use StoreKeeper\StoreKeeper\Model\Export\ProductExportManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTestCase;

class ProductExportDataTest extends AbstractTestCase
{
    protected $productExportManager;
    protected $productCollectionFactory;
    protected $base36Coder;

    protected function setUp(): void
    {
        $this->productCollectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $this->productExportManager = Bootstrap::getObjectManager()->create(ProductExportManager::class);
        $this->base36Coder = Bootstrap::getObjectManager()->create(Base36Coder::class);
    }
    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 1
     * @magentoConfigFixture current_store tax/display/type 3
     * @magentoConfigFixture current_store tax/defaults/country NL
     * @magentoConfigFixture current_store storekeeper_export/featured_attributes_mapping/barcode not-mapped
     * @magentoConfigFixture current_store storekeeper_export/featured_attributes_mapping/brand manufacturer
     * @magentoConfigFixture current_store storekeeper_export/featured_attributes_mapping/fashion_collections not-mapped
     * @magentoConfigFixture current_store storekeeper_export/featured_attributes_mapping/season not-mapped
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/categories.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/tax_classes.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/taxable_simple_product.php
     */
    public function testGetProductExportData()
    {
        $this->assertEquals($this->getTestProductExportData(), $this->getProductExportData());
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getProductExportData(): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToSelect('*');
        $productCollection->addFieldToFilter('sku', ['eq' => 'taxable_product']);
        $productCollection->setStoreId(\Magento\Store\Model\Store::DEFAULT_STORE_ID);
        $productCollection->addMediaGalleryData();
        $productCollection->setOrder('entity_id', 'asc');
        $productExportData = $this->productExportManager->getProductExportData($productCollection->getItems());

        return $this->getFoundEntityData('taxable_product', $productExportData, 'path://product.sku');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTestProductExportData(): array
    {
        return [
            'path://product.type' => 'simple',
            'path://product.sku' => 'taxable_product',
            'path://title' => 'Taxable Product',
            'path://summary' => 'Test short description',
            'path://body' => 'Test description',
            'path://slug' => 'taxable-product',
            'path://seo_title' => 'Test meta title',
            'path://seo_keywords' => 'Test meta keyword',
            'path://seo_description' => 'Test meta description',
            'path://product.active' => 'yes',
            'path://product.product_stock.in_stock' => 'yes',
            'path://product.product_stock.value' => 100,
            'path://product.product_stock.unlimited' => 'yes',
            'path://shop_products.main.backorder_enabled' => 'no',
            'path://product.product_price.tax' => 0.21,
            'path://product.product_price.tax_rate.country_iso2' => 'NL',
            'path://product.product_price.currency_iso3' => 'USD',
            'path://product.product_price.ppu' => 10.0,
            'path://product.product_price.ppu_wt' => 10.0,
            'path://product.product_discount_price.ppu' => 7.0,
            'path://product.product_discount_price.ppu_wt' => 7.0,
            'path://product.product_purchase_price.ppu' => NULL,
            'path://product.product_purchase_price.ppu_wt' => NULL,
            'path://product.product_bottom_price.ppu' => NULL,
            'path://product.product_bottom_price.ppu_wt' => NULL,
            'path://product.product_cost_price.ppu' => 3.0,
            'path://product.product_cost_price.ppu_wt' => 3.0,
            'path://product.configurable_product_kind.alias' => NULL,
            'path://product.configurable_product.sku' => NULL,
            'path://main_category.title' => 'Category 1',
            'path://main_category.slug' => 'category-1',
            'path://extra_category_slugs' => NULL,
            'path://extra_label_slugs' => NULL,
            'path://attribute_set_name' => 'Default',
            'path://attribute_set_alias' => 'default',
            'path://shop_products.main.active' => 'yes',
            'path://shop_products.main.relation_limited' => NULL,
            'path://product.product_images.0.download_url' => NULL,
            'path://product.product_images.1.download_url' => NULL,
            'path://product.product_images.2.download_url' => NULL,
            'path://product.product_images.3.download_url' => NULL,
            'path://product.product_images.4.download_url' => NULL,
            'path://product.product_images.5.download_url' => NULL,
            'path://product.product_images.6.download_url' => NULL,
            'path://product.product_images.7.download_url' => NULL,
            'path://product.product_images.8.download_url' => NULL,
            'path://product.product_images.9.download_url' => NULL,
            'path://content_vars.encoded__' . $this->base36Coder->encode('brand') . '.value' => 'Magento Inc.',
            'path://content_vars.encoded__' . $this->base36Coder->encode('brand') . '.value_label' => 'Manufacturer',
            'path://content_vars.encoded__' . $this->base36Coder->encode('url_key') . '.value' => 'taxable-product',
            'path://content_vars.encoded__' . $this->base36Coder->encode('url_key') . '.value_label' => 'URL Key',
            'path://content_vars.encoded__' . $this->base36Coder->encode('visibility') . '.value' => 'Catalog, Search',
            'path://content_vars.encoded__' . $this->base36Coder->encode('visibility') . '.value_label' => 'Visibility'
        ];
    }
}
