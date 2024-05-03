<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class AttributeSetExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://name',
        'path://alias',
        'path://translatable.lang',
        'path://is_main_lang',
        'path://published'
    ];
    const HEADERS_LABELS = [
        'Name',
        'Alias',
        'Language',
        'Is main language',
        'Published'
    ];

    private StoreManagerInterface $storeManager;
    private Auth $authHelper;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        Auth $authHelper
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
    }

    /**
     * @param array $attributeSets
     * @return array
     */
    public function getAttributeSetExportData(array $attributeSets): array
    {
        $result = [];
        $currentLocale = $this->getCurrentLocale();
        foreach ($attributeSets as $attributeSet) {
            $attributeSetName = $attributeSet->getAttributeSetName();

            $data = [
                $attributeSetName, //path://name
                $this->formatAlias($attributeSetName), //path://alias
                $currentLocale, //'path://translatable.lang'
                'yes', //'path://is_main_lang'
                'true' //'path://published'
            ];
            $result[] = array_combine(self::HEADERS_PATHS, $data);
        }

        return $result;
    }
}
