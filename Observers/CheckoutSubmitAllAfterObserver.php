<?php
namespace StoreKeeper\StoreKeeper\Observers;

class CheckoutSubmitAllAfterObserver implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        \StoreKeeper\StoreKeeper\Helper\Api\Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $order = $observer->getEvent()->getOrder();

        if ($this->authHelper->isConnected($order->getStoreId()) && $order->getStorekeeperOrderPendingSync() == 0) {
            $order = $observer->getEvent()->getOrder();
            $order->setStorekeeperOrderPendingSync(1);
            $order->save();
        }
    }
}
