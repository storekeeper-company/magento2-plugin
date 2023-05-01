<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\TestFramework\TestCase\AbstractController;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Customer\Model\CustomerRegistry;

class OrderCreationTest extends AbstractController
{
    /**
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/customer.php
     */
    public function testOrderCreation()
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->_objectManager->create(ProductRepositoryInterface::class);
        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $this->_objectManager->create(CustomerRegistry::class);
        /** \Magento\Customer\Model\Customer $customer */
        $customer = $customerRegistry->retrieve(1);
        $addressData = $this->getAddresData();
        $billingAddress = $this->_objectManager->create(\Magento\Sales\Model\Order\Address::class, ['data' => $addressData]);
        $billingAddress->setAddressType('billing');
        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');
        $product = $productRepository->get('simple-2');
        $payment = $this->_objectManager->create(\Magento\Sales\Model\Order\Payment::class);
        $payment->setMethod('checkmo');
        $customerIdFromFixture = 1;

        /** @var \Magento\Sales\Model\Order\Item $orderItem */
        $orderItem = $this->_objectManager->create(\Magento\Sales\Model\Order\Item::class);
        $requestInfo = [
            'qty' => 1
        ];
        $orderItem->setProductId($product->getId())
            ->setQtyOrdered(1)
            ->setBasePrice($product->getPrice())
            ->setPrice($product->getPrice())
            ->setRowTotal($product->getPrice())
            ->setProductType($product->getTypeId())
            ->setName($product->getName())
            ->setSku($product->getSku())
            ->setStoreId(0)
            ->setProductId($product->getId())
            ->setSku($product->getSku())
            ->setProductOptions(['info_buyRequest' => $requestInfo]);

        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class);
        $order->setIncrementId('100001001');
        $order->setState(\Magento\Sales\Model\Order::STATE_NEW);
        $order->setStatus($order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_NEW));
        $order->setCustomerIsGuest(false);
        $order->setCustomerId($customer->getId());
        $order->setCustomerEmail($customer->getEmail());
        $order->setCustomerFirstname($customer->getName());
        $order->setCustomerLastname($customer->getLastname());
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
        $order->setAddresses([$billingAddress, $shippingAddress]);
        $order->setPayment($payment);
        $order->addItem($orderItem);
        $order->setStoreId($this->_objectManager->get(\Magento\Store\Model\StoreManagerInterface::class)->getStore()->getId());
        $order->setSubtotal(100);
        $order->setBaseSubtotal(100);
        $order->setBaseGrandTotal(100);

        $orderRepository = $this->_objectManager->create(OrderRepositoryInterface::class);
        $orderRepository->save($order);

        $this->assertEquals(\Magento\Sales\Model\Order::STATE_NEW, $order->getState());
    }

    private function getAddresData()
    {
        return [
            'region' => 'CA',
            'region_id' => '12',
            'postcode' => '11111',
            'company' => 'Test Company',
            'lastname' => 'lastname',
            'firstname' => 'firstname',
            'street' => 'street',
            'city' => 'Los Angeles',
            'email' => 'admin@example.com',
            'telephone' => '11111111',
            'country_id' => 'US'
        ];
    }
}
