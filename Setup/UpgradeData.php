<?php

namespace StoreKeeper\StoreKeeper\Setup;

use Exception;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Customer;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Attributes;

class UpgradeData implements UpgradeDataInterface
{
    private EavSetupFactory $eavSetupFactory;
    private EavConfig $eavConfig;
    private AttributeRepositoryInterface $eavAttributeRepository;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        EavConfig $eavConfig,
        AttributeRepositoryInterface $eavAttributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->eavAttributeRepository = $eavAttributeRepository;
    }

    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '0.0.1', '<')) {
            $this->addStoreKeeperIdAttributes($setup, $context);
        }
    }

    private function addStoreKeeperIdAttributes(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $eavSetup = $this->eavSetupFactory->create([
            'setup' => $setup
        ]);

        try {
            $eavAttribute = $this->eavAttributeRepository->get(Category::ENTITY, 'storekeeper_category_id');
        } catch (Exception $e) {
            $eavSetup->addAttribute(
                Category::ENTITY,
                'storekeeper_category_id',
                [
                    'type' => 'varchar',
                    'label' => 'StoreKeeper Category ID',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 10000,
                    'global' => 2,
                    'group' => Attributes::STOREKEEPER_GROUP_NAME
                ]
            );
        }

        try {
            $eavAttribute = $this->eavAttributeRepository->get(Product::ENTITY, 'storekeeper_product_id');
        } catch (Exception $e) {
            $eavSetup->addAttribute(
                Product::ENTITY,
                'storekeeper_product_id',
                [
                    'type' => 'varchar',
                    'label' => 'StoreKeeper Product ID',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 10000,
                    'global' => 2,
                    'group' => Attributes::STOREKEEPER_GROUP_NAME
                ]
            );
        }

        try {
            $eavAttribute = $this->eavAttributeRepository->get(Customer::ENTITY, 'storekeeper_customer_id');
        } catch (Exception $e) {
            $eavSetup->addAttribute(
                Customer::ENTITY,
                'storekeeper_customer_id',
                [
                    'type' => 'varchar',
                    'label' => 'StoreKeeper Customer ID',
                    'input' => 'text',
                    'required' => false,
                    'sort_order' => 10000,
                    'global' => 2,
                    'group' => Attributes::STOREKEEPER_GROUP_NAME
                ]
            );
        }
    }
}
