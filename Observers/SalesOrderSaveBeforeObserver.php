<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class SalesOrderSaveBeforeObserver implements ObserverInterface
{
    private Auth $authHelper;

    public function __construct(
        Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    /**
     * Set order as pending for sync
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (
            $this->authHelper->isConnected($order->getStoreId()) &&
            $order->getStorekeeperOrderPendingSyncSkip() == false &&
            $order->getStorekeeperOrderPendingSync() == 0
        ) {
            $order = $observer->getEvent()->getOrder();
            $order->setStorekeeperOrderPendingSync(1);
            $order->save();
        }
    }
}
