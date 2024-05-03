<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\TestFramework\Helper\Bootstrap;
use StoreKeeper\StoreKeeper\Model\Export\AttributeSetExportManager;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;

/**
 * @magentoDbIsolation enabled
 */
class AttributeSetExportDataTest extends AbstractTest
{
    protected $attributeSetExportManager;
    protected $attributeSetCollectionFactory;

    protected function setUp(): void
    {
        $this->attributeSetCollectionFactory = Bootstrap::getObjectManager()->create(CollectionFactory::class);
        $this->attributeSetExportManager = Bootstrap::getObjectManager()->create(AttributeSetExportManager::class);
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     */
    public function testGetAttributeSetExportData()
    {
        $this->assertEquals($this->getTestAttributeSetExportData(), $this->getAttributeSetExportData());
    }

    /**
     * @return array
     */
    public function getAttributeSetExportData(): array
    {
        $attributeSetCollection = $this->attributeSetCollectionFactory->create();
        $attributeSets = $attributeSetCollection->addFieldToSelect('*')->getItems();
        $attributeSetExportData = $this->attributeSetExportManager->getAttributeSetExportData($attributeSets);

        return $this->getFoundEntityData('Default', $attributeSetExportData, 'path://name');
    }

    /**
     * @return array
     */
    public function getTestAttributeSetExportData(): array
    {
        return [
            'path://name' => 'Default',
            'path://alias' => 'default',
            'path://translatable.lang' => 'nl',
            'path://is_main_lang' => 'yes',
            'path://published' => 'true'
        ];
    }
}
