<?php

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;

Resolver::getInstance()->requireDataFixture('Magento/Sales/_files/order.php');

$objectManager = Bootstrap::getObjectManager();
/** @var \Magento\Sales\Model\Order $order */
$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(\Magento\Sales\Model\Order::class);
$order->loadByIncrementId('100000001');

$order->setData(
    'base_to_global_rate',
    2
)->setData(
    'base_shipping_amount',
    20
)->setData(
    'base_shipping_canceled',
    2
)->setData(
    'base_shipping_invoiced',
    20
)->setData(
    'base_shipping_refunded',
    3
)->setData(
    'is_virtual',
    0
)->setData(
    'storekeeper_id',
    200
)->setData(
    'order_detached',
    true
)->save();

$orderItems = $order->getItems();
/** @var \Magento\Sales\Api\Data\OrderItemInterface $orderItem */
$orderItem = array_values($orderItems)[0];

/** @var \Magento\Sales\Api\Data\ShipmentItemCreationInterface $shipmentItem */
$invoiceItem = $objectManager->create(\Magento\Sales\Api\Data\InvoiceItemCreationInterface::class);
$invoiceItem->setOrderItemId($orderItem->getItemId());
$invoiceItem->setQty($orderItem->getQtyOrdered());
/** @var \Magento\Sales\Api\InvoiceOrderInterface $invoiceOrder */
$invoiceOrder = $objectManager->create(\Magento\Sales\Api\InvoiceOrderInterface::class);
$invoiceOrder->execute($order->getEntityId(), false, [$invoiceItem]);
