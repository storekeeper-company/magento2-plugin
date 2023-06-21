<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Catalog\Api\Data\CategoryInterface;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Api\StoreConfigManagerInterface;

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

    private StoreManagerInterface $storeManager;
    private CategoryRepositoryInterface $categoryRepository;
    private StoreConfigManagerInterface $storeConfigManager;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        StoreConfigManagerInterface $storeConfigManager
    ) {
        parent::__construct($localeResolver);
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->storeConfigManager = $storeConfigManager;
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
            if ($categoryName == 'Root Catalog') {
                continue;
            }
            $data = [
                $categoryName, //'path://title'
                $currentLocale, //'path://translatable.lang'
                $this->isMainLanguage(), //'path://is_main_lang'
                $category->getUrl(), //'path://slug'
                null, //'path://summary'
                null, //'path://icon'
                $category->getDescription(), //'path://description'
                $category->getMetaTitle(), //'path://seo_title'
                $category->getMetaKeywords(), //'path://seo_keywords'
                $category->getMetaDescription(), //'path://seo_description'
                $this->getCategoryImageUrl($category), //'path://image_url'
                null, //'path://published'
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCategoryImageUrl(CategoryInterface $category): ?string
    {
        $categoryImageUrl = $category->getImageUrl();
        $result = null;

        if (!empty($categoryImageUrl)) {
            $result = rtrim($this->storeManager->getStore()->getBaseUrl(), '/') . $categoryImageUrl;
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

        if (!empty($categoryParentId)) {
            $result = $this->categoryRepository->get($categoryParentId)->getUrl();
        }

        return $result;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getCurrentStoreLanguageCode(): string
    {
        $storeConfigs = $this->storeConfigManager->getStoreConfigs([$this->storeManager->getStore()->getCode()]);
        foreach ($storeConfigs as $config) {
            $languageCode = strstr($config->getCode(), '_', true);
        }

        return $languageCode;
    }

    /**
     * @return string
     */
    private function isMainLanguage(): string
    {
        $result = 'yes';
        if ($this->getCurrentLocale() != $this->getCurrentStoreLanguageCode()) {
            $result = 'no';
        }

        return $result;
    }
}
