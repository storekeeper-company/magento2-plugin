<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Setup\CategorySetup;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture(
    'StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_attribute_sk_color.php'
);
Resolver::getInstance()->requireDataFixture(
    'StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_attribute_sk_size.php'
);
Resolver::getInstance()->requireDataFixture(
    'StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_attribute_sk_shoe_size.php'
);

/** @var ProductRepositoryInterface $productRepository */
$productRepository = Bootstrap::getObjectManager()
    ->get(ProductRepositoryInterface::class);

/** @var $installer CategorySetup */
$installer = Bootstrap::getObjectManager()->create(CategorySetup::class);

/** @var \Magento\Eav\Model\Config $eavConfig */
$eavConfig = Bootstrap::getObjectManager()->get(\Magento\Eav\Model\Config::class);
$firstAttribute = $eavConfig->getAttribute(Product::ENTITY, 'sk_color');
$secondAttribute = $eavConfig->getAttribute(Product::ENTITY, 'sk_size');
$thirdAttribute = $eavConfig->getAttribute(Product::ENTITY, 'sk_shoe_size');

/* Create simple products per each option value*/
/** @var AttributeOptionInterface[] $firstAttributeOptions */
$firstAttributeOptions = $firstAttribute->getOptions();
/** @var AttributeOptionInterface[] $secondAttributeOptions */
$secondAttributeOptions = $secondAttribute->getOptions();
/** @var AttributeOptionInterface[] $thirdAttributeOptions */
$thirdAttributeOptions = $thirdAttribute->getOptions();

$attributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');
$associatedProductIds = [];
$productIds = [10, 20];
$firstAttributeValues = [];
$secondAttributeValues = [];
$thirdAttributeValues = [];
$i = 1;
foreach ($productIds as $productId) {
    $firstOption = $firstAttributeOptions[$i];
    $secondOption = $secondAttributeOptions[$i];
    /** @var $product Product */
    $product = Bootstrap::getObjectManager()->create(Product::class);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('Configurable Option ' . $firstOption->getLabel() . '-' . $secondOption->getLabel())
        ->setSku('simple_' . $productId)
        ->setPrice($productId)
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
    $customAttributes = [
        $firstAttribute->getAttributeCode() => $firstOption->getValue(),
        $secondAttribute->getAttributeCode() => $secondOption->getValue()
    ];
    foreach ($customAttributes as $attributeCode => $attributeValue) {
        $product->setCustomAttributes($customAttributes);
    }
    $product = $productRepository->save($product);

    $firstAttributeValues[] = [
        'label' => 'test first ' . $i,
        'attribute_id' => $firstAttribute->getId(),
        'value_index' => $firstOption->getValue(),
    ];
    $secondAttributeValues[] = [
        'label' => 'test second ' . $i,
        'attribute_id' => $secondAttribute->getId(),
        'value_index' => $secondOption->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
    $i++;
}

/** @var $product Product */
$product = Bootstrap::getObjectManager()->create(Product::class);
/** @var Factory $optionsFactory */
$optionsFactory = Bootstrap::getObjectManager()->create(Factory::class);
$configurableAttributesData = [
    [
        'attribute_id' => $firstAttribute->getId(),
        'code' => $firstAttribute->getAttributeCode(),
        'label' => $firstAttribute->getStoreLabel(),
        'position' => '0',
        'values' => $firstAttributeValues,
    ],
    [
        'attribute_id' => $secondAttribute->getId(),
        'code' => $secondAttribute->getAttributeCode(),
        'label' => $secondAttribute->getStoreLabel(),
        'position' => '1',
        'values' => $secondAttributeValues,
    ],
];
$configurableOptions = $optionsFactory->create($configurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setId(1)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Configurable Product')
    ->setSku('configurable')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->cleanCache();
$productRepository->save($product);

$firstAttributeSetId = $installer->getAttributeSetId('catalog_product', 'Default');
$associatedProductIds = [];
$productIds = [30, 40];
$firstAttributeValues = [];
$secondAttributeValues = [];

foreach ($productIds as $productId) {
    $firstOption = $firstAttributeOptions[$i];
    $secondOption = $secondAttributeOptions[$i];
    /** @var $product Product */
    $product = Bootstrap::getObjectManager()->create(Product::class);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($firstAttributeSetId)
        ->setWebsiteIds([1])
        ->setName('Configurable Option ' . $firstOption->getLabel() . '-' . $secondOption->getLabel())
        ->setSku('simple_' . $productId)
        ->setPrice($productId)
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
    $customAttributes = [
        $firstAttribute->getAttributeCode() => $firstOption->getValue(),
        $secondAttribute->getAttributeCode() => $secondOption->getValue()
    ];
    foreach ($customAttributes as $attributeCode => $attributeValue) {
        $product->setCustomAttributes($customAttributes);
    }
    $product = $productRepository->save($product);

    $firstAttributeValues[] = [
        'label' => 'test sk color ' . $i,
        'attribute_id' => $firstAttribute->getId(),
        'value_index' => $firstOption->getValue(),
    ];
    $secondAttributeValues[] = [
        'label' => 'test sk size ' . $i,
        'attribute_id' => $secondAttribute->getId(),
        'value_index' => $secondOption->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
    $i++;
}

/** @var $product Product */
$product = Bootstrap::getObjectManager()->create(Product::class);

/** @var Factory $optionsFactory */
$optionsFactory = Bootstrap::getObjectManager()->create(Factory::class);

$configurableAttributesData = [
    [
        'attribute_id' => $firstAttribute->getId(),
        'code' => $firstAttribute->getAttributeCode(),
        'label' => $firstAttribute->getStoreLabel(),
        'position' => '0',
        'values' => $firstAttributeValues,
    ],
    [
        'attribute_id' => $secondAttribute->getId(),
        'code' => $secondAttribute->getAttributeCode(),
        'label' => $secondAttribute->getStoreLabel(),
        'position' => '1',
        'values' => $secondAttributeValues,
    ],
];

$configurableOptions = $optionsFactory->create($configurableAttributesData);

$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);

$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setId(11)
    ->setAttributeSetId($firstAttributeSetId)
    ->setWebsiteIds([1])
    ->setName('Configurable Product 12345')
    ->setSku('configurable_12345')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->cleanCache();
$productRepository->save($product);

$associatedProductIds = [];
$productIds = [50];
$firstAttributeValues = [];
$secondAttributeValues = [];
$i = 1;

foreach ($productIds as $productId) {
    $firstOption = $thirdAttributeOptions[$i];
    /** @var $product Product */
    $product = Bootstrap::getObjectManager()->create(Product::class);
    $product->setTypeId(Type::TYPE_SIMPLE)
        ->setId($productId)
        ->setAttributeSetId($attributeSetId)
        ->setWebsiteIds([1])
        ->setName('Configurable Option ' . $firstOption->getLabel())
        ->setSku('simple_' . $productId)
        ->setPrice($productId)
        ->setVisibility(Visibility::VISIBILITY_NOT_VISIBLE)
        ->setStatus(Status::STATUS_ENABLED)
        ->setStockData(['use_config_manage_stock' => 1, 'qty' => 100, 'is_qty_decimal' => 0, 'is_in_stock' => 1]);
    $customAttributes = [
        $thirdAttribute->getAttributeCode() => $firstOption->getValue()
    ];
    foreach ($customAttributes as $attributeCode => $attributeValue) {
        $product->setCustomAttributes($customAttributes);
    }
    $product = $productRepository->save($product);

    $thirdAttributeValues[] = [
        'label' => 'test sk shoe size ' . $i,
        'attribute_id' => $thirdAttribute->getId(),
        'value_index' => $firstOption->getValue(),
    ];
    $associatedProductIds[] = $product->getId();
    $i++;
}

/** @var $product Product */
$product = Bootstrap::getObjectManager()->create(Product::class);
/** @var Factory $optionsFactory */
$optionsFactory = Bootstrap::getObjectManager()->create(Factory::class);
$configurableAttributesData = [
    [
        'attribute_id' => $thirdAttribute->getId(),
        'code' => $thirdAttribute->getAttributeCode(),
        'label' => $thirdAttribute->getStoreLabel(),
        'position' => '0',
        'values' => $thirdAttributeValues,
    ]
];
$configurableOptions = $optionsFactory->create($configurableAttributesData);
$extensionConfigurableAttributes = $product->getExtensionAttributes();
$extensionConfigurableAttributes->setConfigurableProductOptions($configurableOptions);
$extensionConfigurableAttributes->setConfigurableProductLinks($associatedProductIds);
$product->setExtensionAttributes($extensionConfigurableAttributes);

$product->setTypeId(Configurable::TYPE_CODE)
    ->setId(21)
    ->setAttributeSetId($attributeSetId)
    ->setWebsiteIds([1])
    ->setName('Third Configurable Product')
    ->setSku('configurable_w_shoe_size')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(['use_config_manage_stock' => 1, 'is_in_stock' => 1]);
$productRepository->cleanCache();
$productRepository->save($product);
