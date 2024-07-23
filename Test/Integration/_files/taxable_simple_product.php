<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Catalog\Api\ProductAttributeOptionManagementInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeOptionInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\DefaultCategory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();
/** @var WebsiteRepositoryInterface $websiteRepository */
$websiteRepository = $objectManager->get(WebsiteRepositoryInterface::class);
$defaultWebsiteId = $websiteRepository->get('base')->getId();
/** @var DefaultCategory $defaultCategory */
$defaultCategory = $objectManager->get(DefaultCategory::class);
/** @var ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get(ProductRepositoryInterface::class);
$productProductAttributeManagement = $objectManager->get(ProductAttributeManagementInterface::class);
$productAttributeRepository = $objectManager->get(ProductAttributeRepositoryInterface::class);
$attributeOptionManagement = $objectManager->get(ProductAttributeOptionManagementInterface::class);
$attributeOptionInterface = $objectManager->get(AttributeOptionInterfaceFactory::class);
$config = $objectManager->get(\Magento\Catalog\Model\Config::class);

$productRepository->cleanCache();
/** @var ProductInterfaceFactory $productFactory */
$productFactory = $objectManager->get(ProductInterfaceFactory::class);
$product = $productFactory->create();

$registry = $objectManager->get(\Magento\Framework\Registry::class);
$taxRule = $registry->registry('_fixture/Magento_Tax_Model_Calculation_Rule');
$productTaxClassIds = $taxRule->getProductTaxClassIds();
$productTaxClassId = reset($productTaxClassIds);
$group_id = $config->getAttributeGroupId($product->getDefaultAttributeSetId(), 'storekeeper');
$productProductAttributeManagement->assign(
    $product->getDefaultAttributeSetId(),
    $group_id,
    'manufacturer',
    $product->getDefaultAttributeSetId(),
    0
);

$attribute = $productAttributeRepository->get('manufacturer');
$option = $attributeOptionInterface->create(['data' => ['label' => 'Magento Inc.']]);

$optionId = $attributeOptionManagement->add('manufacturer', $option);
$registry = $objectManager->get(\Magento\Framework\Registry::class);
$registry->register('_fixture/option_id', $optionId);
$attribute->unsetData('option');
$productAttributeRepository->save($attribute);


$productData = [
    ProductInterface::TYPE_ID => Type::TYPE_SIMPLE,
    ProductInterface::ATTRIBUTE_SET_ID => $product->getDefaultAttributeSetId(),
    ProductInterface::SKU => 'taxable_product',
    ProductInterface::NAME => 'Taxable Product',
    ProductInterface::PRICE => 10,
    ProductInterface::VISIBILITY => Visibility::VISIBILITY_BOTH,
    ProductInterface::STATUS => Status::STATUS_ENABLED,
    'special_price' => 7,
    'cost' => 3,
    'short_description' => 'Test short description',
    'description' => 'Test description',
    'manufacturer' => $optionId,
    'meta_title' => 'Test meta title',
    'meta_keyword' => 'Test meta keyword',
    'meta_description' => 'Test meta description',
    'website_ids' => [$defaultWebsiteId],
    'stock_data' => [
        'use_config_manage_stock' => 1,
        'qty' => 100,
        'is_qty_decimal' => 0,
        'is_in_stock' => 1,
    ],
    'category_ids' => [3],
    'tax_class_id' => $productTaxClassId, //Taxable Goods
];
$product->setData($productData);

$productRepository->save($product);
