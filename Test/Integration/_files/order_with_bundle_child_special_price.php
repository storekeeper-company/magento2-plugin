<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('StoreKeeper_StoreKeeper::Test/Integration/_files/order.php');
Resolver::getInstance()->requireDataFixture('Magento/Bundle/_files/empty_bundle_product.php');

$objectManager = ObjectManager::getInstance();
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$bundleProduct = $productRepository->get('bundle-product');
$simpleProduct = $productRepository->get('simple');

/** @var Order $order */
$order = $objectManager->get(OrderInterfaceFactory::class)->create()->loadByIncrementId('100000001');

$orderItems = [
    [
        OrderItemInterface::SKU => $bundleProduct->getSku(),
        OrderItemInterface::NAME => $bundleProduct->getName(),
        OrderItemInterface::PRODUCT_ID => $bundleProduct->getId(),
        OrderItemInterface::ORDER_ID => $order->getId(),
        OrderItemInterface::QTY_ORDERED => 1,
        OrderItemInterface::PRICE => 100,
        OrderItemInterface::PRODUCT_TYPE => $bundleProduct->getTypeId(),
        'product_options' => [
            'product_calculations' => 0,
            'info_buyRequest' => [
                'bundle_option' => [1 => 1],
                'bundle_option_qty' => 1,
            ]
        ],
        'children' => [
            [
                OrderItemInterface::SKU => 'bundle_simple_1',
                OrderItemInterface::NAME => 'bundle_simple_1',
                OrderItemInterface::PRODUCT_ID => 1,
                OrderItemInterface::ORDER_ID => $order->getId(),
                OrderItemInterface::QTY_ORDERED => 1,
                OrderItemInterface::ORIGINAL_PRICE => 90,
                OrderItemInterface::PRICE => 40,
                OrderItemInterface::PRICE_INCL_TAX => 48.40,
                OrderItemInterface::PRODUCT_TYPE => 'simple',
                OrderItemInterface::TAX_PERCENT => 21.00,
                'product_options' => [
                    'bundle_selection_attributes' => '{"qty":5, "price":90, "option_label":"Bundle Simple 1"}',
                ],
            ],
        ],
    ],
];

if (!function_exists('saveOrderItems')) {
    /**
     * Save Order Items.
     *
     * @param array $orderItems
     * @param Item|null $parentOrderItem [optional]
     * @return void
     */
    function saveOrderItems(array $orderItems, Order $order, $parentOrderItem = null)
    {
        $objectManager = ObjectManager::getInstance();

        foreach ($orderItems as $orderItemData) {
            /** @var Item $orderItem */
            $orderItem = $objectManager->create(Item::class);
            if (null !== $parentOrderItem) {
                $orderItemData['parent_item'] = $parentOrderItem;
            }
            $orderItem->setData($orderItemData);
            $order->addItem($orderItem);

            if (isset($orderItemData['children'])) {
                saveOrderItems($orderItemData['children'], $order, $orderItem);
            }
        }
    }
}

saveOrderItems($orderItems, $order);
/** @var OrderRepositoryInterface $orderRepository */
$orderRepository = $objectManager->get(OrderRepositoryInterface::class);
$order->setState(Order::STATE_NEW)->setStatus(Order::STATE_NEW);
$order = $orderRepository->save($order);
