<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class SalesOrderInvoicePayObserver implements ObserverInterface
{
    private Auth $authHelper;

    /**
     * Constructor
     *
     * @param Auth $authHelper
     */
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
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        if ($this->authHelper->isConnected($order->getStoreId()) && $order->getStorekeeperOrderPendingSync() == 0) {
            $order->setStorekeeperOrderPendingSync(1);
            $order->save();
        }
    }
}
