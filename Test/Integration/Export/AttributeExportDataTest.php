<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use StoreKeeper\StoreKeeper\Model\Export\AttributeExportManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\TestFramework\Helper\Bootstrap;

class AttributeExportDataTest extends AbstractTest
{
    const TEST_ATTRIBUTE_EXPORT_DATA = [
        'path://name' => 'color',
        'path://label' => 'Color',
        'path://translatable.lang' => 'nl',
        'path://is_main_lang' => 'yes',
        'path://is_options' => 'yes',
        'path://type' => 'string',
        'path://required' => 'no',
        'path://published' => 'true',
        'path://unique' => 'no'
    ];

    protected $attributeExportManager;
    protected $attributeCollectionFactory;

    protected function setUp(): void
    {
        $this->attributeCollectionFactory = Bootstrap::getObjectManager()->create(AttributeFactory::class);
        $this->attributeExportManager = Bootstrap::getObjectManager()->create(AttributeExportManager::class);
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     */
    public function testGetAttributeExportData()
    {
        $this->assertEquals(self::TEST_ATTRIBUTE_EXPORT_DATA, $this->getAttributeExportData());
    }

    /**
     * @return array
     */
    public function getAttributeExportData(): array
    {
        $attributeCollection = $this->attributeCollectionFactory->create()->getCollection();
        $attributeCollection->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, $this->attributeExportManager->getProductEntityTypeId());
        $attributes = $attributeCollection->addFieldToSelect('*')->getItems();
        $attributeExportData = $this->attributeExportManager->getAttributeExportData($attributes);

        return $this->getFoundEntityData('color', $attributeExportData, 'path://name');
    }
}
