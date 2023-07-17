<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Category\FileInfo;

class CategoryExportDataTest extends AbstractTest
{
    protected $categoryExportManager;
    protected $categoryCollectionFactory;
    protected $category;
    protected $store;

    protected function setUp(): void
    {
        $this->categoryCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory::class);
        $this->categoryExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\CategoryExportManager::class);
        $this->category = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\Category::class);
        $this->store = Bootstrap::getObjectManager()->create(\Magento\Store\Model\StoreManagerInterface::class);
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/categories.php
     */
    public function testGetCategoryExportData()
    {
        $c = $this->getTestCategoryExportData();
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categories = $categoryCollection->addFieldToSelect('*')->getItems();
        $categoryExportData = $this->categoryExportManager->getCategoryExportData($categories);
        $this->assertEquals($this->getTestCategoryExportData(), $this->getFoundEntityData('Category 1', $categoryExportData, 'path://title'));
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getTestCategoryExportData(): array
    {
        return [
            'path://title' => 'Category 1',
            'path://translatable.lang' => 'nl',
            'path://is_main_lang' => 'yes',
            'path://slug' => $this->store->getStore()->getBaseUrl() . 'category-1.html',
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
            'path://published' => NULL,
            'path://order' => '1',
            'path://parent_slug' => $this->store->getStore()->getBaseUrl() . 'catalog/category/view/s/default-category/id/2/',
            'path://protected' => NULL
        ];
    }
}
