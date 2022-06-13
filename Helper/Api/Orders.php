<?php

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Model\Order;

class Orders extends AbstractHelper
{

    private Auth $authHelper;

    private Customers $customersHelper;

    /**
     * @param Auth $authHelper
     * @param Customers $customersHelper
     * @param Context $context
     */
    public function __construct(
        Auth $authHelper,
        Customers $customersHelper,
        Context $context
    ) {
        $this->authHelper = $authHelper;
        $this->customersHelper = $customersHelper;

        parent::__construct($context);
    }

    /**
     * @param $order
     * @return array
     */
    public function prepareOrder($order): array
    {
        /** @var $order Order */
        $email = $order->getCustomerEmail();
        $relationDataId = null;
        $orderItemsPayload = $this->prepareOrderItems($order);

        if ($order->getCustomerIsGuest()) {
            $relationDataId = $this->customersHelper->findCustomerRelationDataIdByEmail($email, $order->getStoreId());

            if (!$relationDataId) {
                $relationDataId = $this->customersHelper->createStorekeeperCustomerByOrder($order);
            }
        }

        return [
            'order_items' => $orderItemsPayload,
            'billing_address__merge' => 'false',
            'shipping_address__merge' => 'false',
            'relation_data_id' => $relationDataId,
            'billing_address' => [
                'business_data' => [
                    'name' => $order->getCustomerName(),
                    'country_iso2' => $order->getBillingAddress()->getCountryId()
                ],
                'contact_set' => [
                    'email' => $order->getCustomerEmail(),
                    'name' => $order->getCustomerName(),
                    'phone' => $order->getBillingAddress()->getTelephone()
                ],
                'contact_address' => $this->customersHelper->mapAddress($order->getBillingAddress()),
                'address_billing' => $this->customersHelper->mapAddress($order->getBillingAddress())
            ],
            'shipping_address' => [
                'contact_address' => [
                    'city' => $order->getShippingAddress()->getCity(),
                    'zipcode' => $order->getShippingAddress()->getPostcode(),
                    'street' => $order->getShippingAddress()->getStreet()[0],
                    'streetnumber' => '',
                    'country_iso2' => $order->getShippingAddress()->getCountryId()
                ]
            ]
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    private function prepareOrderItems(Order $order): array
    {
        $payload = [];

        foreach ($order->getItems() as $item) {
            $payloadItem = [
                'sku' => $item->getSku(),
                'ppu_wt' => $item->getPrice(),
                'before_discount_ppu_wt' => (float) $item->getOriginalPrice(),
                'quantity' => $item->getQtyOrdered(),
                'name' => $item->getName(),
                'shop_product_id' => $item->getProduct()->getStorekeeperProductId()
            ];

            $payload[] = $payloadItem;
        }

        $payload[] = [
            'sku' => $order->getShippingMethod(),
            'ppu_wt' => $order->getShippingAmount(),
            'quantity' => 1,
            'name' => $order->getShippingMethod(),
            'is_shipping' => true
        ];

        return $payload;
    }
}
