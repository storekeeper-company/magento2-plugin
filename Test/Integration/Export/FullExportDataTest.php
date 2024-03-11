<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Test\Integration\Export\AttributeExportDataTest;
use StoreKeeper\StoreKeeper\Test\Integration\Export\AttributeOptionExportDataTest;
use StoreKeeper\StoreKeeper\Test\Integration\Export\BlueprintExportDataTest;
use StoreKeeper\StoreKeeper\Test\Integration\Export\CategoryExportDataTest;
use StoreKeeper\StoreKeeper\Test\Integration\Export\CustomerExportDataTest;
use StoreKeeper\StoreKeeper\Test\Integration\Export\ProductExportDataTest;

class FullExportDataTest extends AbstractTest
{
    protected $attributeExportManager;
    protected $attributeCollectionFactory;
        protected $attributeExportDataTest;
    protected $attributeOptionExportManager;
    protected $attributeOptionCollectionFactory;
    protected $attributeOptionExportDataTest;
    protected $blueprintExportManager;
    protected $csvFileContent;
    protected $json;
    protected $blueprintExportDataTest;
    protected $categoryExportManager;
    protected $categoryCollectionFactory;
    protected $category;
    protected $store;
    protected $categoryExportDataTest;
    protected $customerExportManager;
    protected $random;
    protected $localeResolver;
    protected $customerCollectionFactory;
    protected $customerExportDataTest;
    protected $productExportManager;
    protected $productCollectionFactory;
    protected $productExportDataTest;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);

        $this->attributeCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory::class);
        $this->attributeExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\AttributeExportManager::class);
        $this->attributeExportDataTest = $objectManager->getObject(
            AttributeExportDataTest::class,
            [
'attributeCollectionFactory' => $this->attributeCollectionFactory,
'attributeExportManager' => $this->attributeExportManager
            ]
        );
        $this->attributeOptionCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory::class);
        $this->attributeOptionExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\AttributeOptionExportManager::class);
        $this->attributeOptionExportDataTest = $objectManager->getObject(
            AttributeOptionExportDataTest::class,
            [
                'attributeOptionCollectionFactory' => $this->attributeOptionCollectionFactory,
                'attributeOptionExportManager' => $this->attributeOptionExportManager
            ]
        );
        $this->blueprintExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\BlueprintExportManager::class);
        $this->csvFileContent = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\CsvFileContent::class);
        $this->json = Bootstrap::getObjectManager()->create(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->blueprintExportDataTest = $objectManager->getObject(
            BlueprintExportDataTest::class,
            [
                'blueprintExportManager' => $this->blueprintExportManager,
                'csvFileContent' => $this->csvFileContent,
                'json' => $this->json
            ]
        );
        $this->categoryCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);
        $this->categoryExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\CategoryExportManager::class);
        $this->category = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Category::class);
        $this->store = Bootstrap::getObjectManager()->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->categoryExportDataTest = $objectManager->getObject(
            CategoryExportDataTest::class,
            [
                'categoryCollectionFactory' => $this->categoryCollectionFactory,
                'categoryExportManager' => $this->categoryExportManager,
                'category' => $this->category,
                'store' => $this->store
            ]
        );
        $this->random = Bootstrap::getObjectManager()->create(\Magento\Framework\Math\Random::class);
        $this->localeResolver = Bootstrap::getObjectManager()->create(\Magento\Framework\Locale\Resolver::class);
        $this->customerCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Customer\Model\ResourceModel\Customer\CollectionFactory::class);
        $this->customerExportManager = Bootstrap::getObjectManager()->create(
            \StoreKeeper\StoreKeeper\Model\Export\CustomerExportManager::class,
            [
                'random' => $this->random,
                'localeResolver' => $this->localeResolver
            ]
        );
        $this->customerExportDataTest = $objectManager->getObject(
            CustomerExportDataTest::class,
            [
                'random' => $this->random,
                'localeResolver' => $this->localeResolver,
                'customerCollectionFactory' => $this->customerCollectionFactory,
                'customerExportManager' => $this->customerExportManager
            ]
        );
        $this->productCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);
        $this->productExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\ProductExportManager::class);
        $this->productExportDataTest = $objectManager->getObject(
            ProductExportDataTest::class,
            [
                'productCollectionFactory' => $this->productCollectionFactory,
                'productExportManager' => $this->productExportManager
            ]
        );
    }
    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoConfigFixture current_store tax/calculation/price_includes_tax 1
     * @magentoConfigFixture current_store tax/display/type 3
     * @magentoConfigFixture current_store tax/defaults/country NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customers_for_export.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/categories.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/tax_classes.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_products_with_two_attributes.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/taxable_simple_product.php
     */
    public function testFullExportData()
    {
        $this->assertEquals(AttributeExportDataTest::TEST_ATTRIBUTE_EXPORT_DATA, $this->attributeExportDataTest->getAttributeExportData());
        $this->assertEquals(AttributeOptionExportDataTest::TEST_ATTRIBUTE_OPTION_EXPORT_DATA, $this->attributeOptionExportDataTest->getAttributeOptionExportData());
        $this->assertEquals($this->blueprintExportDataTest->getTestBlueprintExportData(), $this->blueprintExportDataTest->getBlueprintCsvContent());
        $this->assertEquals($this->categoryExportDataTest->getTestCategoryExportData(), $this->categoryExportDataTest->getTestCategoryExportData());
        $this->assertEquals(CustomerExportDataTest::CUSTOMER_ONE, array_slice($this->customerExportDataTest->getCustomerExportData()[0], 1));
        $this->assertEquals(CustomerExportDataTest::CUSTOMER_TWO, array_slice($this->customerExportDataTest->getCustomerExportData()[1], 1));
        $this->assertEquals($this->productExportDataTest->getTestProductExportData(), $this->productExportDataTest->getProductExportData());
    }
}
