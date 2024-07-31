<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Model\Export\ProductExportManager;

class CategoryExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://title',
        'path://translatable.lang',
        'path://is_main_lang',
        'path://slug',
        'path://summary',
        'path://icon',
        'path://description',
        'path://seo_title',
        'path://seo_keywords',
        'path://seo_description',
        'path://image_url',
        'path://published',
        'path://order',
        'path://parent_slug',
        'path://protected'
    ];
    const HEADERS_LABELS = [
        'Title',
        'Language',
        'Is main language',
        'Slug',
        'Summary',
        'Icon',
        'Description',
        'SEO title',
        'SEO keywords',
        'SEO description',
        'Image url',
        'Published',
        'Order',
        'Parent slug',
        'Protected'
    ];
    const CATEGORY_ROOT_CATALOG_NAME = 'Root Catalog';

    private CategoryRepositoryInterface $categoryRepository;
    private StoreManagerInterface $storeManager;
    private Auth $authHelper;
    private ProductExportManager $productExportManager;
    private $_defaultCategory;
    private DirectoryList $directoryList;

    /**
     * Constructor
     *
     * @param Resolver $localeResolver
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreConfigManagerInterface $storeConfigManager
     * @param Auth $authHelper
     * @param \StoreKeeper\StoreKeeper\Model\Export\ProductExportManager $productExportManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        StoreConfigManagerInterface $storeConfigManager,
        Auth $authHelper,
        ProductExportManager $productExportManager,
        DirectoryList $directoryList
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->productExportManager = $productExportManager;
        $this->directoryList = $directoryList;
    }

    /**
     * @param array $categories
     * @return array
     */
    public function getCategoryExportData(array $categories): array
    {
        $result = [];
        $currentLocale = $this->getCurrentLocale();
        foreach ($categories as $category) {
            $categoryName = $category->getName();
            $defaultCategoryId = $this->getDefaultCategoryId($category);

            if ($categoryName == self::CATEGORY_ROOT_CATALOG_NAME || $category->getId() == $defaultCategoryId) {
                continue;
            }

            $categoryImage = $this->getCategoryImageUrl($category->getImageUrl());

            $data = [
                $categoryName, //'path://title'
                $currentLocale, //'path://translatable.lang'
                'yes', //'path://is_main_lang'
                $category->getUrlKey(), //'path://slug'
                null, //'path://summary'
                null, //'path://icon'
                $category->getDescription(), //'path://description'
                $category->getMetaTitle(), //'path://seo_title'
                $category->getMetaKeywords(), //'path://seo_keywords'
                $category->getMetaDescription(), //'path://seo_description'
                $categoryImage, //'path://image_url'
                1, //'path://published'
                $category->getPosition(), //'path://order'
                $this->getCategoryParentUrl($category), //'path://parent_slug'
                null, //'path://protected'
            ];
            $result[] = array_combine(self::HEADERS_PATHS, $data);
        }

        return $result;
    }

    /**
     * @param CategoryInterface $category
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCategoryParentUrl(CategoryInterface $category): ?string
    {
        $categoryParentId = $category->getParentId();
        $result = null;

        if (!empty($categoryParentId) && $categoryParentId != $this->_defaultCategory) {
            $result = $this->categoryRepository->get($categoryParentId)->getUrlKey();
        }

        return $result;
    }

    /**
     * @param CategoryInterface $category
     * @return string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getDefaultCategoryId(CategoryInterface $category): ?string
    {
        if (!isset($this->_defaultCategory)) {
            $this->_defaultCategory = $category->getStore()->getRootCategoryId();
        }

        return $this->_defaultCategory;
    }

    /**
     * @param string|null $image
     * @return string|null
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCategoryImageUrl(?string $image): ?string
    {
        $imageUrl = null;

        if ($image) {
            $mediaBaseDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::PUB);
            $imagePath = $mediaBaseDir . $image;
            if ($this->productExportManager->isImageFormatAllowed($imagePath)) {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl();
                $imageUrl = rtrim($mediaUrl, '/') . $image;
            }
        }

        return $imageUrl;
    }
}
