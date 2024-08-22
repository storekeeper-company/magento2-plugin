<?php

namespace StoreKeeper\StoreKeeper\Setup;

use Magento\Catalog\Model\ResourceModel\Product\Gallery;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @inheritDoc
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $setup->getConnection()->addColumn(
            $setup->getTable(Gallery::GALLERY_TABLE),
            'storekeeper_image_id',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                'unsigned' => true,
                'nullable' => true,
                'comment' => 'StoreKeeper Image Id'
            ]
        );

        $setup->endSetup();
    }
}
