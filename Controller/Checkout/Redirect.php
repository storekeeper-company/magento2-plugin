<?php

namespace StoreKeeper\StoreKeeper\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Api\Data\OrderInterface;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
use StoreKeeper\StoreKeeper\Model\OrderItems;
use StoreKeeper\StoreKeeper\Model\CustomerInfo;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;
use StoreKeeper\ApiWrapper\ModuleApiWrapper;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;

class Redirect extends Action
{
    private const FINISH_PAGE_ROUTE = 'storekeeper_payment/checkout/finish';
    private Session $checkoutSession;
    private QuoteRepository $quoteRepository;
    private OrderRepository $orderRepository;
    private Auth $authHelper;
    private OrdersHelper $ordersHelper;
    private OrderApiClient $orderApiClient;

    /**
     * Redirect constructor
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param OrderRepository $orderRepository
     * @param Auth $authHelper
     * @param OrdersHelper $ordersHelper
     * @param OrderApiClient $orderApiClient
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        Auth $authHelper,
        OrdersHelper $ordersHelper,
        OrderApiClient $orderApiClient
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->authHelper = $authHelper;
        $this->ordersHelper = $ordersHelper;
        $this->orderApiClient = $orderApiClient;
        parent::__construct($context);
    }

    /**
     * Redirect action to SK payment methods
     *
     * @return void
     */
    public function execute(): void
    {
        try {
            $storeKeeperPaymentMethodId = (int)$this->getRequest()->getParam('storekeeper_payment_method_id');
            $order = $this->checkoutSession->getLastRealOrder();
            $payload = $this->ordersHelper->prepareOrder($order, false);
            $redirect_url =  $this->_url->getUrl(self::FINISH_PAGE_ROUTE);
            $shopModule = $this->orderApiClient->getShopModule($order->getStoreid());
            $products = $this->getPaymentProductFromOrderItems($payload['order_items']);
            $billingInfo = $this->applyAddressName($payload['billing_address'] ?? $payload['shipping_address']);

            $payment = $this->getStoreKeeperPayment($storeKeeperPaymentMethodId, $shopModule, $redirect_url, $order, $payload, $billingInfo, $products);

            $order->setStorekeeperPaymentId($payment['id']);

            try {
                $this->orderRepository->save($order);
            } catch (GeneralException $e) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($e->getMessage())
                );
            }

            if (empty($order)) {
                throw new Error('No order found in session, please try again');
            }

            # Restore the quote
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(true)->setReservedOrderId(null);
            $this->checkoutSession->replaceQuote($quote);
            $this->quoteRepository->save($quote);

            $this->getResponse()->setNoCacheHeaders();
            $this->getResponse()->setRedirect($payment['payment_url']);

        } catch (\Exception $e) {
            $this->_getCheckoutSession()->restoreQuote();
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->messageManager->addExceptionMessage($e, $e->getMessage());

            $this->_redirect('checkout/cart');
        }
    }

    /**
     * Get checkout sessions
     *
     * @return Session
     */
    protected function _getCheckoutSession(): Session
    {
        return $this->checkoutSession;
    }

    /**
     * Get product from order items
     *
     * @param array $items
     * @return array
     */
    protected function getPaymentProductFromOrderItems(array $items): array
    {
        $products = [];
        foreach ($items as $orderProduct) {
            $products[] = array_intersect_key(
                $orderProduct,
                array_flip(['sku', 'name', 'ppu_wt', 'quantity', 'is_shipping', 'is_payment', 'is_discount'])
            );
        }
        return $products;
    }

    /**
     * Apply address name
     *
     * @param array $customerInfo
     * @return array
     */
    protected function applyAddressName(array $customerInfo): array
    {
        $personName = implode(
            ' ',
            array_filter(
                [
                    $customerInfo['contact_person']['firstname'] ?? null,
                    $customerInfo['contact_person']['familyname_prefix'] ?? null,
                    $customerInfo['contact_person']['familyname'] ?? null,
                ]
            )
        );
        $customerInfo['name'] = $customerInfo['name'] ?? $customerInfo['business_data']['name'] ?? $personName;
        $customerInfo['contact_address']['name'] = $customerInfo['contact_address']['name']??  $personName;

        return $customerInfo;
    }

    /**
     * Get Order total
     *
     * @param array $items
     * @param ModuleApiWrapper $shopModule
     * @return float
     */
    function getOrderTotal(array $items, ModuleApiWrapper $shopModule): float
    {
        $order = $shopModule->newVolatileOrder(array(
            'order_items' => $items,
        ));

        return $order['value_wt'];
    }

    /**
     * Get SK payment
     *
     * @param ?int $storeKeeperPaymentMethodId
     * @param ModuleApiWrapper $shopModule
     * @param string $redirect_url
     * @param OrderInterface $order
     * @param array $payload
     * @param array $billingInfo
     * @param array $products
     * @return array
     */
    private function getStoreKeeperPayment(
        ?int $storeKeeperPaymentMethodId,
        ModuleApiWrapper $shopModule,
        string $redirect_url,
        OrderInterface $order,
        array $payload,
        array $billingInfo,
        array $products
    ): array {
        if ($storeKeeperPaymentMethodId) {
            return $shopModule->newWebShopPaymentWithReturn(
                [
                    'redirect_url' => $redirect_url . '?trx={{trx}}&orderID=' . $order->getId(),
                    'amount' => $this->getOrderTotal($payload['order_items'], $shopModule),
                    'title' => 'Order: ' . $order->getIncrementId(),
                    'provider_method_id' => $storeKeeperPaymentMethodId,
                    'relation_data_id' => $payload['relation_data_id'],
                    'relation_data_snapshot' => $billingInfo,
                    'end_user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'products' => $products,
                ]
            );
        } else {
            return $shopModule->newLinkWebShopPaymentForHookWithReturn(
                [
                    'redirect_url' => $redirect_url . '?trx={{trx}}&orderID=' . $order->getId(),
                    'amount' => $this->getOrderTotal($payload['order_items'], $shopModule),
                    'title' => 'Order: ' . $order->getIncrementId(),
                    'relation_data_id' => $payload['relation_data_id'],
                    'relation_data_snapshot' => $billingInfo,
                    'end_user_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'products' => $products,
                ]
            );
        }
    }
}
