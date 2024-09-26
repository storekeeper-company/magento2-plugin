<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Helper\Api\Categories as ApiCategories;
use StoreKeeper\StoreKeeper\Helper\ProductDescription as DescriptionHelper;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTestCase;
use StoreKeeper\StoreKeeper\Model\Consumer;

class ImportExistingCategoryTest extends AbstractTestCase
{
    protected const WEBHOOK_DATA = "{\"type\":\"created\",\"entity\":\"Category\",\"storeId\":\"1\",\"module\":\"BlogModule\",\"key\":\"id\",\"value\":\"1\",\"refund\":false}";
    protected $apiCategories;
    protected $categoryFactory;
    protected $categoryCollectionFactory;
    protected $categoryRepository;
    protected $consumer;
    protected $descriptionHelper;

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);
        $this->categoryCollectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $this->categoryFactory = Bootstrap::getObjectManager()->create(CategoryFactory::class);
        $this->orderApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\OrderApiClient::class);
        $this->categoryRepository = Bootstrap::getObjectManager()->create(CategoryRepository::class);
        $this->descriptionHelper = Bootstrap::getObjectManager()->create(DescriptionHelper::class);


        $this->orderApiClientMock->method('listTranslatedCategoryForHooks')
            ->willReturn($this->getCategoryImportData());

        $this->apiCategories = $objectManager->getObject(
            ApiCategories::class,
            [
                'orderApiClient' => $this->orderApiClientMock,
                'categoryCollectionFactory' => $this->categoryCollectionFactory,
                'categoryRepository' => $this->categoryRepository,
                'descriptionHelper' => $this->descriptionHelper
            ]
        );

        $this->consumer = $objectManager->getObject(
            Consumer::class,
            [
                'categoriesHelper' => $this->apiCategories
            ]
        );
    }

    /**
     * Feed queue consumer with category event "created" and verify that chanes applied to magento category
     *
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/categories.php
     */
    public function testProcess()
    {
        //Load existing category
        $category = $this->categoryFactory->create()->loadByAttribute('url_key', $this->getStoreKeeperTestCategorySlug());
        $categoryNameBefore = $category->getName();
        $categoryDescriptionBefore = $category->getDescription();
        $categoryMetaTitleBefore = $category->getMetaTitle();
        $categoryMetaKeywordsBefore = $category->getMetaKeywords();
        $categoryMetaDescriptionBefore = $category->getMetaDescription();
        
        //Send webhook data to consumer
        $this->consumer->process(self::WEBHOOK_DATA);
        
        //Reload updated category from repository
        $category = $this->categoryFactory->create()->loadByAttribute('url_key', $this->getStoreKeeperTestCategorySlug());
        $categoryNameAfter = $category->getName();
        $categoryDescriptionAfter = $category->getDescription();
        $categoryMetaTitleAfter = $category->getMetaTitle();
        $categoryMetaKeywordsAfter = $category->getMetaKeywords();
        $categoryMetaDescriptionAfter = $category->getMetaDescription();

        //Assert that old info differs from new info
        $this->assertNotEquals($categoryNameBefore, $categoryNameAfter);
        $this->assertNotEquals($categoryDescriptionBefore, $categoryDescriptionAfter);
        $this->assertNotEquals($categoryMetaTitleBefore, $categoryMetaTitleAfter);
        $this->assertNotEquals($categoryMetaKeywordsBefore, $categoryMetaKeywordsAfter);
        $this->assertNotEquals($categoryMetaDescriptionBefore, $categoryMetaDescriptionAfter);
    }

    public function getCategoryImportData(): array
    {
        return [
            'data' => [
                0 => [
                    'id' => 1,
                    'title' => 'Category 1 from Api',
                    'published' => true,
                    'slug' => 'category-1',
                    'description' => 'Category 1 Meta Description from Api',
                    'seo_title' => 'Category 1 Meta Title from Api',
                    'seo_keywords' => 'Category 1 Meta Keywords from Api',
                    'seo_description' => 'Category 1 Meta Description from Api'
                ]
            ],
            'total' => 1,
            'count' => 1
        ];
    }

    public function getStoreKeeperTestCategorySlug(): string
    {
        return $this->getCategoryImportData()['data'][0]['slug'];
    }
}
