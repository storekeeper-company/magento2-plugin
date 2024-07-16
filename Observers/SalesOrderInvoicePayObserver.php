<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use StoreKeeper\StoreKeeper\Api\PaymentApiClient;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class SalesOrderInvoicePayObserver implements ObserverInterface
{
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;
    private PaymentApiClient $paymentApiClient;
    private OrderResource $orderResource;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     * @param Json $json
     * @param PublisherInterface $publisher
     * @param PaymentApiClient $paymentApiClient
     * @param OrderResource $orderResource
     */
    public function __construct(
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher,
        PaymentApiClient $paymentApiClient,
        OrderResource $orderResource
    ) {
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->paymentApiClient = $paymentApiClient;
        $this->orderResource = $orderResource;
    }

    /**
     * Sync invoice creation for non-SK payment methods
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $invoice = $observer->getEvent()->getInvoice();
        if ($invoice->getState() == \Magento\Sales\Model\Order\Invoice::STATE_PAID) {
            $order = $invoice->getOrder();
            $storekeeperId = $order->getStorekeeperId();
            if (
                $this->authHelper->isConnected($order->getStoreId())
                && !$this->paymentApiClient->isStorekeeperPayment($order->getPayment()->getMethod())
                && $this->authHelper->isOrderSyncEnabled($order->getStoreId())
                && !$order->getOrderDetached()
            ) {
                $storekeeperPaymentId = $this->paymentApiClient->newWebPayment(
                    $order->getStoreId(),
                    [
                        'amount' => $order->getTotalPaid(),
                        'description' => __('Payment by Magento plugin (Order #%1)', $order->getIncrementId())
                    ]
                );

                if ($storekeeperId) {
                    $this->paymentApiClient->attachPaymentIdsToOrder(
                        $order->getStoreId(),
                        $storekeeperId,
                        [
                            $storekeeperPaymentId
                        ]
                    );
                }

                $order->setData('storekeeper_payment_id', $storekeeperPaymentId);
                $this->orderResource->saveAttribute($order, 'storekeeper_payment_id');
            }
        }
    }
}
