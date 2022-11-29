<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;

class SalesOrderSaveBeforeObserver implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        \StoreKeeper\StoreKeeper\Helper\Api\Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    /**
     * Handle post order registration and order tieing to guest customer
     *
     * @param Observer $observer
     * @event checkout_submit_after
     *
     * @return void
     */
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