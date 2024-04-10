<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;

class AttributeOptionExportDataTest extends AbstractTest
{
    const TEST_ATTRIBUTE_OPTION_EXPORT_DATA = [
        'path://name' => 'gender_1',
        'path://label' => 'Male',
        'path://translatable.lang' => 'nl',
        'path://is_main_lang' => 'yes',
        'path://is_default' => 'no',
        'path://image_url' => NULL,
        'path://attribute.name' => 'gender',
        'path://attribute.label' => 'Gender'
    ];

    protected $attributeOptionExportManager;
    protected $attributeOptionCollectionFactory;

    protected function setUp(): void
    {
        $this->attributeOptionCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory::class);
        $this->attributeOptionExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\AttributeOptionExportManager::class);
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     */
    public function testGetAttributeOptionExportData()
    {
        $this->assertEquals(self::TEST_ATTRIBUTE_OPTION_EXPORT_DATA, $this->getAttributeOptionExportData());
    }

    /**
     * @return array
     */
    public function getAttributeOptionExportData(): array
    {
        $attributeOptionCollection = $this->attributeOptionCollectionFactory->create();
        $attributeOptions = $attributeOptionCollection->addFieldToSelect('*')->getItems();
        $attributeOptionExportData = $this->attributeOptionExportManager->getAttributeOptionExportData($attributeOptions);

        return $this->getFoundEntityData('gender_1', $attributeOptionExportData, 'path://name');
    }
}
