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
use Magento\Sales\Model\ResourceModel\Order\Tax\Item as TaxItem;
use Magento\Shipping\Model\ShipmentNotifier;
use Psr\Log\LoggerInterface;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Bundle\Model\Product\Type as Bundle;

class Orders extends AbstractHelper
{
    private const BUNDLE_TYPE = 'bundle';

    private const CONFIGURABLE_TYPE = 'configurable';

    private Auth $authHelper;

    private Customers $customersHelper;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private OrderRepositoryInterface $orderRepository;

    private ConvertOrder $convertOrder;

    private ShipmentNotifier $shipmentNotifier;

    private ShipmentRepositoryInterface $shipmentRepository;

    private TrackFactory $trackFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    private Json $jsonSerializer;

    private Bundle $bundle;

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
     * @param Json $jsonSerializer
     * @param Bundle $bundle
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
        Context $context,
        LoggerInterface $logger,
        Json $jsonSerializer,
        Bundle $bundle
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
        $this->logger = $logger;
        $this->jsonSerializer = $jsonSerializer;
        $this->bundle = $bundle;

        parent::__construct($context);
    }

    /**
     * @param $order
     * @param $isUpdate
     * @return array
     */
    public function prepareOrder(Order $order, bool $isUpdate): array
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
                    'name' => $order->getBillingAddress()->getCompany(),
                    'country_iso2' => $order->getBillingAddress()->getCountryId()
                ],
                'contact_set' => [
                    'email' => $order->getCustomerEmail(),
                    'name' => $order->getBillingAddress()->getName(),
                    'phone' => $order->getBillingAddress()->getTelephone()
                ],
                'contact_person' => [
                    'firstname' => $order->getBillingAddress()->getFirstName(),
                    'familyname_prefix' => '',
                    'familyname' => $order->getBillingAddress()->getLastName(),
                ],
                'name' => $order->getBillingAddress()->getCompany() ?
                    $order->getBillingAddress()->getCompany() :
                    $order->getBillingAddress()->getName(),
                'contact_address' => $this->customersHelper->mapAddress($order->getBillingAddress()),
                'address_billing' => $this->customersHelper->mapAddress($order->getBillingAddress())
            ]
        ];

        if (!$order->getIsVirtual()) {
            $payload['shipping_address'] = [
                'business_data' => [
                    'name' => $order->getShippingAddress()->getCompany(),
                    'country_iso2' => $order->getShippingAddress()->getCountryId()
                ],
                'contact_set' => [
                    'email' => $order->getCustomerEmail(),
                    'name' => $order->getShippingAddress()->getName(),
                    'phone' => $order->getShippingAddress()->getTelephone()
                ],
                'contact_person' => [
                    'firstname' => $order->getShippingAddress()->getFirstName(),
                    'familyname_prefix' => '',
                    'familyname' => $order->getShippingAddress()->getLastName(),
                ],
                'name' => $order->getShippingAddress()->getCompany() ?
                    $order->getShippingAddress()->getCompany() :
                    $order->getShippingAddress()->getName(),
                'contact_address' => $this->customersHelper->mapAddress($order->getShippingAddress())
            ];
        }

        if (!$isUpdate) {
            $payload['order_items'] = $orderItemsPayload;
            $payload['shop_order_number'] = $order->getIncrementId();
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
            if ($item->getProductType() == self::BUNDLE_TYPE) {
                $bundleId = $item->getProductId();
                $payloadItems = $this->getBundlePayload($item, $taxFreeId, $rates);
            } else {
                $parentIds = $this->bundle->getParentIdsByChild($item->getProductId());
                if ($item->getParentItemId() || (isset($bundleId) && in_array($bundleId, $parentIds))) {
                    continue;
                }
                $payloadItems[] = $this->getSimpleProductPayload($item, $taxFreeId, $rates);
            }

            $payload = array_merge($payload, $payloadItems);
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
            } elseif (!empty($taxFreeId)) {
                $payloadItem['tax_rate_id'] = $taxFreeId;
            }

            $payload[] = $payloadItem;
        }

        if ($discountAmount = (float) $order->getDiscountAmount()) {
            $payload[] = [
                'is_discount' => true,
                'name' => __("Discount"),
                'sku' => 'DS-101',
                'ppu_wt' => $discountAmount,
                'quantity' => 1,
                'tax_rate_id' => $taxFreeId, // tax rate for discounted products
            ];
        }

        return $payload;
    }

    public function getStoreKeeperOrder($storeId, $storeKeeperId)
    {
        try {
            $response = $this->authHelper->getModule('ShopModule', $storeId)->getOrder($storeKeeperId);
            if (is_array($response)) {
                return $response;
            }
        } catch (\Exception $e) {
            $this->logger->error($exception->getMessage());
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

        if ($order && $order->getStatus() !== 'canceled') {
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
                    $shipment->getExtensionAttributes()->setIsStorekeeper(true);
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

        if ($storeKeeperId > 0) {
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

        if ($order->getStatus() != 'closed' && $storeKeeperOrder['status'] != 'canceled') {
            if (isset($statusMapping[$storeKeeperOrder['status']])) {
                if ($statusMapping[$storeKeeperOrder['status']] !== $order->getStatus() && $storeKeeperOrder['status'] !== 'complete') {
                    $this->updateStoreKeeperOrderStatus($order, $storeKeeperId);
                }
            }

            $this->updateStoreKeeperOrder($order, $storeKeeperId);
            $this->createShipment($order, $storeKeeperId);
        }

        if ($this->hasRefund($order)) {
            $this->applyRefund($order);
        }

        $order->setStorekeeperOrderLastSync(time());
        $order->setStorekeeperOrderPendingSync(0);
        $order->setStorekeeperOrderPendingSyncSkip(true);
        $order->setStorekeeperOrderNumber($storeKeeperOrder['number']);

        try {
            $this->orderRepository->save($order);
        } catch (GeneralException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
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
            if ($status = array_search($order->getStatus(), $statusMapping)) {
                $this->authHelper->getModule('ShopModule', $order->getStoreId())->updateOrderStatus([
                    'status' => $status
                ], $storeKeeperId);
            }
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
        $storeKeeperOrder = $this->authHelper->getModule('ShopModule', $order->getStoreid())->newOrderWithReturn($payload);
        $storeKeeperId = $storeKeeperOrder['id'];
        $order->setStorekeeperId($storeKeeperId);
        $order->setStorekeeperOrderLastSync(time());
        $order->setStorekeeperOrderPendingSync(0);
        $order->setStorekeeperOrderPendingSyncSkip(true);
        $order->setStorekeeperOrderNumber($storeKeeperOrder['number']);

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
                'storekeeper_order_pending_sync',
                1,
                'eq'
            )
            ->setPageSize(
                $pageSize
            )
            ->setCurrentPage($page)
            ->create();

        return $this->orderRepository->getList($searchCriteria);
    }

    /**
     * @param $item
     * @return float
     */
    private function getItemPrice($item): float
    {
        $itemPrice = 0.0;
        $parentProduct = $item->getParentItem();
        if ($parentProduct) {
            $parentProductType = $parentProduct->getProductType();
            if ($parentProductType == self::BUNDLE_TYPE) {
                $itemPrice = $item->getPrice();
            } elseif ($parentProductType == self::CONFIGURABLE_TYPE) {
                $itemPrice = $parentProduct->getPrice();
            }
        } else {
            $itemPrice = $item->getPrice();
        }

        return $itemPrice;
    }

    /**
     * @param $item
     * @param $taxFreeId
     * @param $rates
     * @return array
     */
    private function getBundlePayload($item, $taxFreeId, $rates)
    {
        // total of bundle's items prices as simple products
        $bundleItemsPriceTotal = null;

        // total of bundle's items prices as option products
        $bundleOptionItemsTotal = null;

        $bundlePrice = $item->getPriceInclTax();

        $parentProduct = $this->getParentProductData($item);

        foreach ($item->getChildrenItems() as $bundleItem) {
            $bundleItemSku = $bundleItem->getSku();
            $bundleItemPrice = $bundleItem->getProduct()->getPrice();
            $bundleItemsPriceTotal += $bundleItemPrice;
            $bundleItemData = $this->jsonSerializer->unserialize(
                $bundleItem->getProductOptions()['bundle_selection_attributes']
            );
            $bundleOptionItemsTotal += $bundleItemData['price'];

            $hasDiscount = $bundleItem->getDiscountPercent() != 0;
            $bundleItemWithDiscountData = $hasDiscount ? $this->getBundleItemWithDiscountData($bundleItem) : null;

            $bundlePayload[] = [
                'quantity' => $bundleItem->getQtyOrdered(),
                'before_discount_ppu_wt' => $hasDiscount ? $bundleItemWithDiscountData['before_discount_ppu_wt'] : null,
                'ppu_wt' => $hasDiscount ? $bundleItemWithDiscountData['ppu_wt'] : $bundleItemData['price'],
                'sku' => $bundleItemSku,
                'name' => $bundleItem->getName(),
                'description' => $bundleItemData['option_label'],
                'tax_rate_id' => $this->getTaxRateId($item, $taxFreeId, $rates),
                'extra' => [
                    'external_id' => $bundleItem->getProduct()->getId(),
                    'options' => [
                        'option' => $bundleItemData['option_label']
                    ],
                    'parent_product' => $parentProduct
                ]
            ];
        }

        $bundleDiscount = $this->getBundleDiscount((float)$bundleItemsPriceTotal, (float)$bundlePrice);

        if ($bundleDiscount && $bundleOptionItemsTotal != 0) {
            $bundlePayload[] = $this->getBundleDiscountData($bundleDiscount, $parentProduct);
        }

        if ($bundleOptionItemsTotal == 0 && $bundlePrice > $bundleOptionItemsTotal) {
            $bundlePayload[] = $this->getBundleCompensateData($bundlePrice, $parentProduct, $item, $taxFreeId, $rates);
        }

        return $bundlePayload;
    }

    /**
     * @param $item
     * @param $taxFreeId
     * @param $rates
     * @return array
     */
    private function getSimpleProductPayload($item, $taxFreeId, $rates)
    {
        $isConfigurableProduct = $item->getProductType() == self::CONFIGURABLE_TYPE;

        if ($isConfigurableProduct) {
            $payloadItem = $this->getConfigurableProductData($item);
        } else {
            $shopProductId = '';

            if ($item->getProduct() !== null && $item->getProduct()->getStorekeeperProductId()) {
                $shopProductId = $item->getProduct()->getStorekeeperProductId();
            }

            $payloadItem = [
                'sku' => $item->getSku(),
                'quantity' => $item->getQtyOrdered(),
                'name' => $item->getName(),
                'shop_product_id' => $shopProductId,
            ];
        }

        if (((float)$item->getTaxAmount()) > 0) {
            $payloadItem['ppu_wt'] = $item->getPriceInclTax();
            $payloadItem['before_discount_ppu_wt'] = (float) $item->getOriginalPrice();
        } else {
            $payloadItem['ppu_wt'] = $this->getItemPrice($item);
        }

        $payloadItem['tax_rate_id'] = $this->getTaxRateId($item, $taxFreeId, $rates);

        return $payloadItem;
    }

    /**
     * @param $item
     * @return array
     */
    private function getParentProductData($item)
    {
        return [
            'external_id' => $item->getProductId(),
            'sku' => $item->getProduct()->getSku(),
            'name' => $item->getProduct()->getName()
        ];
    }

    /**
     * @param $bundleItemsPriceTotal
     * @param $bundlePrice
     * @return mixed
     */
    private function getBundleDiscount($bundleItemsPriceTotal, $bundlePrice)
    {
        return $bundlePrice - $bundleItemsPriceTotal;
    }

    /**
     * @param $bundleDiscount
     * @param $parentProduct
     * @return array
     */
    private function getBundleDiscountData($bundleDiscount, $parentProduct)
    {
        return [
            'quantity' => 1,
            'ppu_wt' => $bundleDiscount,
            'sku' => $parentProduct['sku'],
            'is_discount' => true,
            'name' => $parentProduct['name']
        ];
    }

    /**
     * @param $bundleItem
     * @return array
     */
    private function getBundleItemWithDiscountData($bundleItem)
    {
        return [
            'before_discount_ppu_wt' => $bundleItem->getPrice(),
            'ppu_wt' => $bundleItem->getPrice() - $bundleItem->getDiscountAmount()
        ];
    }

    /**
     * @param $bundlePrice
     * @param $parentProduct
     * @param $item
     * @param $taxFreeId
     * @return array
     */
    private function getBundleCompensateData($bundlePrice, $parentProduct, $item, $taxFreeId, $rates)
    {
        return [
            'quantity' => 1,
            'ppu_wt' => $bundlePrice,
            'sku' => $parentProduct['sku'],
            'name' => $parentProduct['name'],
            'description' => $parentProduct['name'],
            'tax_rate_id' => $this->getTaxRateId($item, $taxFreeId, $rates),
            'extra' => [
                'external_id' => $parentProduct['external_id'],
                'parent_product' => $parentProduct
            ]
        ];
    }

    /**
     * @param $item
     * @return array
     */
    private function getConfigurableProductData($item)
    {
        foreach ($item->getProductOptions()['attributes_info'] as $attribute) {
            $productConfigurableAttributes[$attribute['label']] = $attribute['value'];
        }
        foreach ($item->getChildrenItems() as $configurableProductOption) {
            $configurableProductData = [
                'sku' => $configurableProductOption->getSku(),
                'quantity' => $item->getQtyOrdered(),
                'name' => $configurableProductOption->getName(),
                'description' => $configurableProductOption->getDescription(),
                'extra' => [
                    'external_id' => $configurableProductOption->getProductId(),
                    'options' => $productConfigurableAttributes,
                    'parent_product' => $this->getParentProductData($item)
                ]
            ];
        }

        return $configurableProductData;
    }

    /**
     * @param $item
     * @param $taxFreeId
     * @param $rates
     * @return mixed|null
     */
    private function getTaxRateId($item, $taxFreeId, $rates)
    {
        $taxPercent = ((float) $item->getTaxPercent()) / 100;
        if ($taxPercent > 0) {
            $rateId = null;
            foreach ($rates['data'] ?? [] as $rate) {
                if ($rate['value'] == $taxPercent) {
                    $rateId = $rate['id'];
                    break;
                }
            }
            $taxRateId = $rateId;
        } elseif (!empty($taxFreeId)) {
            $taxRateId = $taxFreeId;
        }

        return $taxRateId;
    }
}
