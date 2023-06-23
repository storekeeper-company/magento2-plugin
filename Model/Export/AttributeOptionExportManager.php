<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Catalog\Api\Data\CategoryInterface;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use StoreKeeper\StoreKeeper\Model\Export\CsvFileContent;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Api\Data\AttributeOptionInterface;

class AttributeOptionExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://name',
        'path://label',
        'path://translatable.lang',
        'path://is_main_lang',
        'path://is_default',
        'path://image_url',
        'path://attribute.name',
        'path://attribute.label',
        'path://date_created',
        'path://date_updated'
    ];
    const HEADERS_LABELS = [
        'Name',
        'Label',
        'Language',
        'Is main language',
        'Is default',
        'Image URL',
        'Attribute name',
        'Attribute label',
        'Date created',
        'Date updated'
    ];

    private Attribute $attribute;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        Attribute $attribute
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager);
        $this->attribute = $attribute;
    }

    /**
     * @param array $attributeOptions
     * @return array
     */
    public function getAttributeOptionExportData(array $attributeOptions): array
    {
        $result = [];
        $currentLocale = $this->getCurrentLocale();
        foreach ($attributeOptions as $attributeOption) {
            $attribute = $this->getAttribute($attributeOption);
            $attributeData = $this->getAttributeData($attribute);
            $attributeOptionId = $attributeOption->getId();
            $attributeOptionData = $this->getAttributeOptionData($attribute, $attributeOption);
            $data = [
                isset($attributeData['attribute_code']) ? $attributeData['attribute_code'] . '_' . $attributeOptionId : null, //path://name'
                $attributeOptionData['label'], //'path://label'
                $this->getCurrentLocale(), //'path://translatable.lang'
                $this->isMainLanguage(), //'path://is_main_lang'
                $attribute->getDefaultValue() == $attributeOptionId ? 'yes' : 'no', //path://is_default'
                null, //'path://image_url'
                $attribute->getAttributeCode(), //'path://attribute.name'
                $attribute->getFrontendLabel(), //'path://attribute.label'
                null, //'path://date_created'
                null, //'path://date_updated'
            ];
            $result[] = array_combine(self::HEADERS_PATHS, $data);
        }

        return $result;
    }

    /**
     * @param AttributeOptionInterface $attributeOption
     * @return Attribute
     */
    private function getAttribute(AttributeOptionInterface $attributeOption): Attribute
    {
        return $this->attribute->load($attributeOption->getAttributeId());
    }

    /**
     * @param Attribute $attribute
     * @return array
     */
    private function getAttributeData(Attribute $attribute): array
    {
        $data = [
            'attribute_code' => $attribute->getAttributeCode(),
            'frontend_label' => $attribute->getFrontendLabel()
        ];

        return $data;
    }

    /**
     * @param Attribute $attribute
     * @param AttributeOptionInterface $attributeOption
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttributeOptionData(Attribute $attribute, AttributeOptionInterface $attributeOption): array
    {
        $data = [
            'label' => $attribute->getSource()->getOptionText($attributeOption->getId())
        ];

        return $data;
    }
}
