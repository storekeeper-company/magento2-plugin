<?php

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Shipping\Model\ShipmentNotifier;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;

class Orders extends AbstractHelper
{

    private Auth $authHelper;

    private Customers $customersHelper;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private OrderRepositoryInterface $orderRepository;

    private ConvertOrder $convertOrder;

    private ShipmentNotifier $shipmentNotifier;

    private ShipmentRepositoryInterface $shipmentRepository;

    private TrackFactory $trackFactory;

    /**
     * @param Auth $authHelper
     * @param Customers $customersHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param ConvertOrder $convertOrder
     * @param ShipmentNotifier $shipmentNotifier
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param TrackFactory $trackFactory
     * @param Context $context
     */
    public function __construct(
        Auth $authHelper,
        Customers $customersHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        ConvertOrder $convertOrder,
        ShipmentNotifier $shipmentNotifier,
        ShipmentRepositoryInterface $shipmentRepository,
        TrackFactory $trackFactory,
        TaxItem $taxItem,
        Context $context
    ) {
        $this->authHelper = $authHelper;
        $this->customersHelper = $customersHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->convertOrder = $convertOrder;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->shipmentRepository = $shipmentRepository;
        $this->trackFactory = $trackFactory;
        $this->taxItem = $taxItem;

        parent::__construct($context);
    }

    /**
     * @param $order
     * @param $isUpdate
     * @return array
     */
    public function prepareOrder($order, $isUpdate): array
    {

        /** @var $order Order */
        $email = $order->getCustomerEmail();
        $relationDataId = null;
        $orderItemsPayload = $this->prepareOrderItems($order);

        $relationDataId = $this->customersHelper->findCustomerRelationDataIdByEmail($email, $order->getStoreId());

        if (!$relationDataId) {
            $relationDataId = $this->customersHelper->createStorekeeperCustomerByOrder($order);
        }

        $payload = [
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
            ]
        ];

        if (!$order->getIsVirtual()) {
            $payload['shipping_address'] = [
                'contact_address' => [
                    'city' => $order->getBillingAddress()->getCity(),
                    'zipcode' => $order->getShippingAddress()->getPostcode(),
                    'street' => $order->getShippingAddress()->getStreet()[0],
                    'streetnumber' => '',
                    'country_iso2' => $order->getShippingAddress()->getCountryId()
                ]
            ];
        }

        if ($discountAmount = (float) $order->getDiscountAmount()) {
            $orderItemsPayload[] = [
                'is_discount' => true,
                'name' => __("Discount"),
                'sku' => 'DS-101',
                'ppu_wt' => $discountAmount,
                'quantity' => 1,
                'tax_rate_id' => 1, // tax rate for discounted products
            ];
        }

        if (!$isUpdate) {
            $payload['order_items'] = $orderItemsPayload;
        } else {
            $payload['order_items__remove'] = null;
            $payload['order_items__do_not_change'] = true;
        }

        if ($order->getCouponCode()) {
            $payload['order_coupon_codes'] = [
                [
                    'code' => $order->getCouponCode(),
                    'value_wt' => $order->getDiscountAmount()
                ]
            ];
        }

        return $payload;
    }

    public function hasRefund(Order $order)
    {
        return $order->getTotalRefunded() > 0;
    }

    public function refundAllOrderItems(Order $order, $storeKeeperId, array $refund_payments)
    {
        $this->authHelper->getModule('ShopModule', $order->getStoreId())
            ->refundAllOrderItems([
                'id' => $storeKeeperId,
                'refund_payments' => $refund_payments
            ]);
    }

    public function applyRefund(Order $order)
    {
        $totalRefunded = $order->getTotalRefunded();

        if ((float) $totalRefunded > 0) {

            $storekeeperId = $order->getStorekeeperId();
            $storeKeeperOrder = $this->authHelper->getModule('ShopModule', $order->getStoreId())->getOrder($storekeeperId);

            // check if the difference between Magento 2 and StoreKeeper exists
            $diff = ((float)$totalRefunded) - $storeKeeperOrder['paid_back_value_wt'];

            // check if the difference is a positive number
            // magento_refund - storekeeper_refund = pending_refund
            // 90             - 70                 = 20
            // in this above example we'll have to refund 20
            if ($diff > 0) {

                $storekeeperRefundId = $this->newWebPayment(
                    $order->getStoreId(),
                    [
                        'amount' => round(-abs($diff), 2),
                        'description' => __('Refund by Magento plugin (Order #%1)', $order->getIncrementId())
                    ]
                );

                $this->attachPaymentIdsToOrder(
                    $order->getStoreId(),
                    $storekeeperId,
                    [
                        $storekeeperRefundId
                    ]
                );

            }

            if ($totalRefunded == $order->getTotalPaid()) {
                if ($storeKeeperOrder['status'] != 'refunded') {
                    $this->refundAllOrderItems($order, $storekeeperId, [

                    ]);  
                }
            }
        }
    }

    public function newWebPayment($storeId, $parameters = []) 
    {
        // $args = [ 'amount' => 20, 'description' => 'My Description' ]
        return $this->authHelper->getModule('PaymentModule', $storeId)
            ->newWebPayment($parameters);
    }

    public function attachPaymentIdsToOrder($storeId, $storeKeeperId, $paymentIds = [])
    {
        $this->authHelper->getModule('ShopModule', $storeId)
            ->attachPaymentIdsToOrder(['payment_ids' => $paymentIds], $storeKeeperId);
    }

    /**
     * @param Order $order
     * @return array
     */
    private function prepareOrderItems(Order $order): array
    {
        $payload = [];

        $rates = [];
        $taxFreeId = null;

        $rates = $this->authHelper->getTaxRates($order->getStoreId(), 'WO');
        foreach ($rates['data'] ?? [] as $rate) {
            if ($rate['alias'] == 'special_applicable_not_vat') {
                $taxFreeId = $rate['id'];
                break;
            }
        }

        if ($order->getTaxAmount() > 0) {
            $rates = $this->authHelper->getTaxRates($order->getStoreId(), $order->getBillingAddress()->getCountryId());
        }

        foreach ($order->getItems() as $item) {
            $shopProductId = '';

            if ($item->getProduct() !== null && $item->getProduct()->getStorekeeperProductId()) {
                $shopProductId = $item->getProduct()->getStorekeeperProductId();
            }

            $payloadItem = [
                'sku' => $item->getSku(),
                // keep this here for future reference
                // 'ppu_wt' => $item->getPriceInclTax(),
                // 'before_discount_ppu_wt' => (float) $item->getOriginalPrice(),
                'quantity' => $item->getQtyOrdered(),
                'name' => $item->getName(),
                'shop_product_id' => $shopProductId,
            ];

            if (((float)$item->getTaxAmount()) > 0) {
                $payloadItem['ppu_wt'] = $item->getPriceInclTax();
                $payloadItem['before_discount_ppu_wt'] = (float) $item->getOriginalPrice();
            } else {
                $payloadItem['ppu_wt'] = $item->getPrice();
            }

            $taxPercent = ((float) $item->getTaxPercent()) / 100;
            if ($taxPercent > 0) {
                $rateId = null;
                foreach ($rates['data'] ?? [] as $rate) {
                    if ($rate['value'] == $taxPercent) {
                        $rateId = $rate['id'];
                        break;
                    }
                }
                $payloadItem['tax_rate_id'] = $rateId;
            } else if (!empty($taxFreeId)) {
                $payloadItem['tax_rate_id'] = $taxFreeId;
            }
            $payload[] = $payloadItem;
        }

        if (!$order->getIsVirtual()) {
            $payloadItem = [
                'sku' => $order->getShippingMethod(),
                'ppu_wt' => $order->getShippingAmount() + $order->getShippingTaxAmount(),
                'quantity' => 1,
                'name' => $order->getShippingDescription(),
                'is_shipping' => true,
            ];

            if ($order->getShippingTaxAmount() > 0) {
                $tax_items = $this->taxItem->getTaxItemsByOrderId($order->getId());

                if (!empty($tax_items)) {
                    foreach ($tax_items as $tax_item) {
                        if ($tax_item['taxable_item_type'] == 'shipping') {
                            $taxPercent = ((float) $tax_item['tax_percent']) / 100;
                            if ($taxPercent > 0) {
                                $rateId = null;
                                foreach ($rates['data'] as $rate) {
                                    if ($rate['value'] == $taxPercent) {
                                        $rateId = $rate['id'];
                                    }
                                }
                                $payloadItem['tax_rate_id'] = $rateId;

                            }
                            break;
                        }
                    }
                }
            } else if (!empty($taxFreeId)) {
                $payloadItem['tax_rate_id'] = $taxFreeId;
            }

            $payload[] = $payloadItem;
        }

        return $payload;
    }

    public function getStoreKeeperOrder($storeId, $storeKeeperId)
    {
        try {
            if (is_array($response = $this->authHelper->getModule('ShopModule', $storeId)->getOrder($storeKeeperId))) {
                return $response;
            }
        } catch (\Error|\Exception $e) {
            return null;
        }
    }

    /**
     * @throws LocalizedException
     */
    public function updateById($storeId, $storeKeeperId)
    {
        $storeKeeperOrder = $this->getStoreKeeperOrder($storeId, $storeKeeperId);

        if ($storeKeeperOrder === null) {
            return;
        }

        /** @var Order $order */
        $order = $this->getOrderByStoreKeeperId($storeKeeperId);

        if ($order->getStatus() !== 'canceled') {
            $this->createShipment($order, $storeKeeperId);
        }
    }

    public function createShipment(Order $order, $storeKeeperId)
    {
        $storeKeeperOrder = $this->getStoreKeeperOrder($order->getStoreId(), $storeKeeperId);

        if (isset($storeKeeperOrder['shipped_item_no']) && !$order->hasShipments()) {
            $shippedItem = (int) $storeKeeperOrder['shipped_item_no'];

            if ($shippedItem > 0) {
                if (!$order->canShip()) {
                    throw new LocalizedException(
                        __('You can\'t create an shipment.')
                    );
                }

                $shipment = $this->convertOrder->toShipment($order);

                foreach ($order->getAllItems() as $orderItem) {
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }

                    $qtyShipped = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qtyShipped);

                    $shipment->addItem($shipmentItem);
                }

                $track = $this->trackFactory->create();
                $statusUrl = $this->authHelper->getModule('ShopModule', $order->getStoreId())->getOrderStatusPageUrl($storeKeeperId);
                $track->setNumber($storeKeeperId);
                $track->setCarrierCode($storeKeeperOrder['shipping_name']);
                $track->setTitle('StoreKeeper Shipment Tracking Number');
                $track->setDescription('Shipping ');
                $track->setUrl($statusUrl);

                $shipment->addTrack($track);

                $shipment->register();
                $shipment->getOrder()->setIsInProcess(true);

                try {
                    $this->shipmentRepository->save($shipment);

                    $this->orderRepository->save($shipment->getOrder());

                    $this->shipmentNotifier->notify($shipment);

                    $this->shipmentRepository->save($shipment);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }
    }

    public function getOrderByStoreKeeperId($storeKeeperId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('storekeeper_id', $storeKeeperId, 'eq')->create();

        return current($this->orderRepository->getList($searchCriteria)->getItems());
    }

    private function getResultStoreKeeperId($result)
    {
        return $result['order_id'];
    }

    public function exists($order)
    {
        $storeKeeperId = $order->getStorekeeperId();

        if ($storeKeeperId > 0 ) {
            return $storeKeeperId;
        }

        return false;
    }

    /**
     * @throws LocalizedException
     */
    public function update($order, $storeKeeperId)
    {
        $storeKeeperOrder = $this->getStoreKeeperOrder($order->getStoreId(), $storeKeeperId);

        // it might be so that this store has been previously connected and has "old"
        // StoreKeeper orders
        if (empty($storeKeeperOrder)) {
            return;
        }

        $statusMapping = $this->statusMapping();
        if (!isset($storeKeeperOrder['status'])) {            
            // no status
            return;
        }

        if (!isset($statusMapping[$storeKeeperOrder['status']])) {
            // unsupported status
            return;
        }

        if ($order->getStatus() != 'closed' && $storeKeeperOrder['status'] != 'canceled') {
            if ($statusMapping[$storeKeeperOrder['status']] !== $order->getStatus() && $storeKeeperOrder['status'] !== 'complete') {
                $this->updateStoreKeeperOrderStatus($order, $storeKeeperId);
            }
    
            $this->updateStoreKeeperOrder($order, $storeKeeperId);
            $this->createShipment($order, $storeKeeperId);
        }

        if ($this->hasRefund($order)) {
            $this->applyRefund($order);
        }

    }

    /**
     * @return string[]
     */
    private function statusMapping(): array
    {
        return [
            'complete' => 'complete',
            'processing' => 'processing',
            'cancelled' => 'canceled',
            'new' => 'new'
        ];
    }

    public function updateStoreKeeperOrderStatus(Order $order, $storeKeeperId)
    {
        $statusMapping = $this->statusMapping();

        try {
            $this->authHelper->getModule('ShopModule', $order->getStoreId())->updateOrderStatus(['status' => array_search($order->getStatus(), $statusMapping)], $storeKeeperId);
        } catch (GeneralException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    public function updateStoreKeeperOrder(Order $order, $storeKeeperId)
    {
        $payload = $this->prepareOrder($order, true);

        try {
            $this->authHelper->getModule('ShopModule', $order->getStoreId())->updateOrder($payload, $storeKeeperId);
        } catch (GeneralException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    public function onCreate(Order $order)
    {
        $payload = $this->prepareOrder($order, false);
        $storeKeeperId = $this->authHelper->getModule('ShopModule', $order->getStoreId())->newOrder($payload);
        $order->setStorekeeperId($storeKeeperId);

        try {
            $this->orderRepository->save($order);
        } catch (GeneralException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }

        if ($order->getPaymentsCollection()->count() && $order->getStatus() !== 'canceled') {
            $paymentId = $this->authHelper->getModule('PaymentModule', $order->getStoreId())->newWebPayment([
                'amount' => $order->getGrandTotal(),
                'description' => $order->getCustomerNote()
            ]);

            if ($paymentId) {
                try {
                    $this->authHelper->getModule('ShopModule', $order->getStoreId())->attachPaymentIdsToOrder(['payment_ids' => [$paymentId]], $storeKeeperId);
                } catch (GeneralException $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __($e->getMessage())
                    );
                }
            }
        }

        if ($this->hasRefund($order)) {
            $this->applyRefund($order);
        }
    }

    /**
     * @param $storeId
     * @param $page
     * @param $pageSize
     * @return OrderSearchResultInterface
     */
    public function getOrders($storeId, $page, $pageSize): OrderSearchResultInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'store_id',
                $storeId,
                'eq'
            )
            ->addFilter(
                'status',
                ['processing', 'canceled', 'closed', 'complete'],
                'in'
            )
            ->setPageSize(
                $pageSize
            )
            ->setCurrentPage($page)
            ->create();

        return $this->orderRepository->getList($searchCriteria);
    }
}
