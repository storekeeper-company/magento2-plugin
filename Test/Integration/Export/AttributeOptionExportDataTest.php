<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Model\Export\AttributeOptionExportManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;

class AttributeOptionExportDataTest extends AbstractTest
{
    const TEST_ATTRIBUTE_OPTION_EXPORT_DATA = [
        'path://name' => 'sk_color_4',
        'path://label' => 'First Option 1',
        'path://translatable.lang' => 'nl',
        'path://is_main_lang' => 'yes',
        'path://is_default' => 'no',
        'path://image_url' => NULL,
        'path://attribute.name' => 'sk_color',
        'path://attribute.label' => 'Storekeeper Color'
    ];

    protected $attributeOptionExportManager;
    protected $attributeOptionCollectionFactory;

    protected function setUp(): void
    {
        $this->attributeOptionCollectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $this->attributeOptionExportManager = Bootstrap::getObjectManager()->create(AttributeOptionExportManager::class);
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_attribute_sk_color.php
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

        return $this->getFoundEntityData('sk_color_4', $attributeOptionExportData, 'path://name');
    }
}
