<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Catalog\Api\Data\CategoryInterface;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Store\Api\StoreConfigManagerInterface;

class AttributeExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://name',
        'path://label',
        'path://translatable.lang',
        'path://is_main_lang',
        'path://is_options',
        'path://type',
        'path://required',
        'path://published',
        'path://unique',
        'path://attribute_set.encoded__7q8z3wdrtro.is_assigned'
    ];
    const HEADERS_LABELS = [
        'Name',
        'Label',
        'Language',
        'Is main language',
        'Has options',
        'Options type',
        'Required',
        'Published',
        'Unique',
        'Default'
    ];

    private CategoryHelper $categoryHelper;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        StoreConfigManagerInterface $storeConfigManager
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager);
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getAttributeExportData(array $attributes): array
    {
        $result = [];
        $currentLocale = $this->getCurrentLocale();
        foreach ($attributes as $attribute) {
            $hasOption = $attribute->usesSource();
            $data = [
                $attribute->getAttributeCode(), //'path://name'
                $attribute->getFrontendLabel(), //'path://label'
                $this->getCurrentLocale(), //'path://translatable.lang'
                $this->isMainLanguage(), //'path://is_main_lang'
                $hasOption ? 'yes' : 'no', //'path://is_options'
                $hasOption ? 'string' : null, //'path://type'
                $attribute->getIsRequired() ? 'yes' : 'no', //'path://required'
                null, //'path://published'
                $attribute->getIsUnique() ? 'yes' : 'no', //'path://unique'
                null, //'path://attribute_set.encoded__7q8z3wdrtro.is_assigned'
            ];
            $result[] = array_combine(self::HEADERS_PATHS, $data);
        }

        return $result;
    }
}
