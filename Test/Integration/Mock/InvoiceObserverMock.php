<?php
namespace StoreKeeper\StoreKeeper\Test\Integration\Mock;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class InvoiceObserverMock implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        // Dummy data for disabling original observer
    }
}
