<?php declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Setup\Patch\Data;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Setup\SalesSetupFactory;

class RelationDataId implements DataPatchInterface
{

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var SalesSetupFactory
     */
    private $salesSetupFactory;

    /**
     * @var CustomerSetupFactory
     */
    private $customerSetupFactory;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var CustomerSetup
     */
    private $customerSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CustomerSetupFactory $customerSetupFactory,
        AttributeResource $attributeResource,
        SalesSetupFactory $salesSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->customerSetupFactory = $customerSetupFactory;
        $this->customerSetup = $customerSetupFactory->create(['setup' => $moduleDataSetup]);
        $this->attributeResource = $attributeResource;
        $this->salesSetupFactory = $salesSetupFactory;
    }
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        try {
            $salesSetup = $this->salesSetupFactory->create(['setup' => $this->moduleDataSetup]);

//            $salesSetup->addAttribute(
//                Order::ENTITY,
//                'relation_data_id',
//                [
//                    'type' => 'int',
//                    'visible' => false,
//                    'required' => true
//                ]
//            );

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
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            var_dump($e->getTraceAsString());
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
