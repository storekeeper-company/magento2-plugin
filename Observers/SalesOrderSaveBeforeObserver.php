<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class SalesOrderSaveBeforeObserver implements ObserverInterface
{
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;

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
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (
            $this->authHelper->isConnected($order->getStoreId()) &&
            $order->getStorekeeperOrderPendingSyncSkip() == false &&
            $order->getStorekeeperOrderPendingSync() == 0
        ) {
            $order = $observer->getEvent()->getOrder();

            $this->publisher->publish(
                \StoreKeeper\StoreKeeper\Model\OrderSync\Consumer::CONSUMER_NAME,
                $this->json->serialize(['orderId' => $order->getId()])
            );

            $order->setStorekeeperOrderPendingSync(1);
            $order->save();
        }
    }
}
