<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class CheckoutSubmitAllAfterObserver implements ObserverInterface
{
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     */
    public function __construct(
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher
    ) {
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
    }

    /**
     * Set order as pending for sync
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();
        $storeId = $this->authHelper->getStoreId($order->getStoreId());

        if (
            $this->authHelper->isConnected($storeId)
            && $order->getStorekeeperOrderPendingSync() == 0
            && $this->authHelper->isOrderSyncEnabled($storeId)
            && !$order->getOrderDetached()
        ) {
            $order = $observer->getEvent()->getOrder();

            $this->publisher->publish(
                \StoreKeeper\StoreKeeper\Model\OrderSync\Consumer::CONSUMER_NAME,
                $this->json->serialize(['orderId' => $order->getIncrementId()])
            );
        }
    }
}
