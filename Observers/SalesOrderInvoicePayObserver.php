<?php
namespace StoreKeeper\StoreKeeper\Observers;

class SalesOrderInvoicePayObserver implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(
        \StoreKeeper\StoreKeeper\Helper\Api\Auth $authHelper
    ) {
        $this->authHelper = $authHelper;
    }

    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $invoice = $observer->getEvent()->getInvoice();
        $order = $invoice->getOrder();

        if ($this->authHelper->isConnected($order->getStoreId()) && $order->getStorekeeperOrderPendingSync() == 0) {
            $order->setStorekeeperOrderPendingSync(1);
            $order->save();
        }
    }
}
