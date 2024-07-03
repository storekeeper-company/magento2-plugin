<?php

namespace StoreKeeper\StoreKeeper\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Api\Data\OrderInterface;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
use StoreKeeper\StoreKeeper\Api\OrderApiClient;
use StoreKeeper\StoreKeeper\Api\PaymentApiClient;
use StoreKeeper\StoreKeeper\Logger\Logger;
use StoreKeeper\StoreKeeper\Model\OrderItems;
use StoreKeeper\StoreKeeper\Model\CustomerInfo;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Api\Orders as OrdersHelper;

class Redirect extends Action
{
    private const FINISH_PAGE_ROUTE = 'storekeeper_payment/checkout/finish';
    private Session $checkoutSession;
    private QuoteRepository $quoteRepository;
    private OrderRepository $orderRepository;
    private Auth $authHelper;
    private OrdersHelper $ordersHelper;
    private OrderApiClient $orderApiClient;
    private PaymentApiClient $paymentApiClient;
    private Json $json;
    private PublisherInterface $publisher;
    private Logger $logger;

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
     * @param PaymentApiClient $paymentApiClient
     * @param Logger $logger
     * @param Json $json
     * @param PublisherInterface $publisher
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        QuoteRepository $quoteRepository,
        OrderRepository $orderRepository,
        Auth $authHelper,
        OrdersHelper $ordersHelper,
        OrderApiClient $orderApiClient,
        PaymentApiClient $paymentApiClient,
        Logger $logger,
        Json $json,
        PublisherInterface $publisher
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->orderRepository = $orderRepository;
        $this->authHelper = $authHelper;
        $this->ordersHelper = $ordersHelper;
        $this->orderApiClient = $orderApiClient;
        $this->paymentApiClient = $paymentApiClient;
        $this->logger = $logger;
        $this->json = $json;
        $this->publisher = $publisher;
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
            $this->logger->error('SK Order ID: ' . $order->getStorekeeperId() );
            $this->logger->error('Order Number: ' . $order->getStorekeepperOrderNumber() );

            if (empty($order)) {
                throw new Error('No order found in session, please try again');
            }

            $payload = $this->ordersHelper->prepareOrder($order, false);
            $redirect_url =  $this->_url->getUrl(self::FINISH_PAGE_ROUTE);
            $shopModule = $this->orderApiClient->getShopModule($order->getStoreid());
            $products = $this->getPaymentProductFromOrderItems($payload['order_items']);
            $billingInfo = $this->applyAddressName(
                $payload['billing_address'] ?? $payload['shipping_address']
            );
            $payment = $this->paymentApiClient->getStoreKeeperPayment(
                $storeKeeperPaymentMethodId,
                $redirect_url,
                $order,
                $payload,
                $billingInfo,
                $products
            );

            if (array_key_exists('id', $payment)) {
                $order->setStorekeeperPaymentId($payment['id']);
                $this->orderRepository->save($order);

                $this->publisher->publish(
                    \StoreKeeper\StoreKeeper\Model\OrderSync\Consumer::CONSUMER_NAME,
                    $this->json->serialize(['orderId' => $order->getIncrementId()])
                );
            }

            # Restore the quote
            $quote = $this->quoteRepository->get($order->getQuoteId());
            $quote->setIsActive(true)->setReservedOrderId(null);
            $this->checkoutSession->replaceQuote($quote);
            $this->quoteRepository->save($quote);

            $this->_response->setNoCacheHeaders();
            $this->_response->setRedirect($payment['payment_url']);

        } catch (\Exception $e) {
            $this->_getCheckoutSession()->restoreQuote();
            $this->messageManager->addExceptionMessage($e, __('Something went wrong, please try again later'));
            $this->logger->error(
                'Error while processing payment info during order sync',
                [
                    'error' =>  $this->logger->buildReportData($e),
                    'payload' =>  $payload,
                    'storeId' =>  $storeId
                ]
            );

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
}
