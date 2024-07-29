<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Category\FileInfo;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Model\Export\CategoryExportManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTestCase;

class CategoryExportDataTest extends AbstractTestCase
{
    protected $categoryExportManager;
    protected $categoryCollectionFactory;
    protected $category;
    protected $store;

    protected function setUp(): void
    {
        $this->categoryCollectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $this->categoryExportManager = Bootstrap::getObjectManager()->create(CategoryExportManager::class);
        $this->category = Bootstrap::getObjectManager()->create(Category::class);
        $this->store = Bootstrap::getObjectManager()->create(StoreManagerInterface::class);
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/categories.php
     */
    public function testGetCategoryExportData()
    {
        $this->assertEquals($this->getTestCategoryExportData(), $this->getCategoryExportData());
    }

    /**
     * @return array
     */
    public function getCategoryExportData(): array
    {
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categories = $categoryCollection->addFieldToSelect('*')->getItems();
        $categoryExportData = $this->categoryExportManager->getCategoryExportData($categories);

        return $this->getFoundEntityData('Category 1', $categoryExportData, 'path://title');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTestCategoryExportData(): array
    {
        return [
            'path://title' => 'Category 1',
            'path://translatable.lang' => 'nl',
            'path://is_main_lang' => 'yes',
            'path://slug' => 'category-1',
            'path://summary' => NULL,
            'path://icon' => NULL,
            'path://description' => 'Category 1 Description',
            'path://seo_title' => 'Category 1 Meta Title',
            'path://seo_keywords' => 'Category 1 Meta Keywords',
            'path://seo_description' => 'Category 1 Meta Description',
            'path://image_url' =>
                $this->store->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
                . ltrim(FileInfo::ENTITY_MEDIA_PATH, '/')
                . '/'
                . 'magento_small_image.jpg',
            'path://published' => 1,
            'path://order' => '1',
            'path://parent_slug' => null,
            'path://protected' => NULL
        ];
    }
}
