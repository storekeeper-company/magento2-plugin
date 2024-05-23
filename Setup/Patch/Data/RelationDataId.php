<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;
use StoreKeeper\StoreKeeper\Logger\Logger;

class RelationDataId implements SchemaPatchInterface
{
    private SchemaSetupInterface $schemaSetup;
    private SalesSetupFactory $salesSetupFactory;
    private CustomerSetupFactory $customerSetupFactory;
    private AttributeResource $attributeResource;
    private CustomerSetup $customerSetup;
    private Logger $logger;

    /**
     * Constructor
     *
     * @param SchemaSetupInterface $schemaSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param AttributeResource $attributeResource
     * @param SalesSetupFactory $salesSetupFactory
     * @param Logger $logger
     */
    public function __construct(
        SchemaSetupInterface $schemaSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeResource $attributeResource,
        SalesSetupFactory $salesSetupFactory,
        Logger $logger
    ) {
        $this->schemaSetup = $schemaSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->customerSetup = $customerSetupFactory->create(['setup' => $schemaSetup]);
        $this->attributeResource = $attributeResource;
        $this->salesSetupFactory = $salesSetupFactory;
        $this->logger = $logger;
    }

    /**
     * @return RelationDataId|void
     */
    public function apply()
    {
        $this->schemaSetup->getConnection()->startSetup();

        $salesSetup = $this->salesSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $salesSetup->addAttribute(
            Order::ENTITY,
            'relation_data_id',
            [
                'type' => 'int',
                'visible' => false,
                'required' => true
            ]
        );

        $this->customerSetup->addAttribute(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            'relation_data_id',
            [
                'label' => __('Relation Data ID'),
                'required' => 0,
                'position' => 200,
                'system' => 0,
                'user_defined' => 1,
                'is_used_in_grid' => 1,
                'is_visible_in_grid' => 1,
                'is_filterable_in_grid' => 1,
                'is_searchable_in_grid' => 1,
            ]
        );

        $this->customerSetup->addAttributeSet(
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER,
            null,
            'relation_data_id'
        );

        $attribute = $this->customerSetup->getEavConfig()
            ->getAttribute(CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER, 'relation_data_id');

        $attribute->setData('used_in_forms', [
            'adminhtml_customer'
        ]);

        $this->attributeResource->save($attribute);

        $this->schemaSetup->getConnection()->endSetup();
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        return [];
    }
}
