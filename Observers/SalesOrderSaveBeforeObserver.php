<?php
namespace StoreKeeper\StoreKeeper\Observers;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Api\Orders;

class SalesOrderSaveBeforeObserver implements ObserverInterface
{
    private Auth $authHelper;
    private Json $json;
    private PublisherInterface $publisher;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(
        Auth $authHelper,
        Json $json,
        PublisherInterface $publisher,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->authHelper = $authHelper;
        $this->json = $json;
        $this->publisher = $publisher;
        $this->orderRepository = $orderRepository;
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

        if ($order->getId()) {
            if (
                $this->authHelper->isConnected($order->getStoreId())
                && $order->getStorekeeperOrderPendingSync() == 0
                && $this->authHelper->isOrderSyncEnabled($order->getStoreId())
            ) {
                $order = $observer->getEvent()->getOrder();
                $oldOrder = $this->orderRepository->get($order->getId());
                $oldStatus = $order->getOrigData('status');

                //Send order to sync only if status is updated and exist in sk mapping list
                if ($order->getStatus() != $oldStatus && in_array($order->getStatus(), Orders::statusMapping())) {
                    $this->publisher->publish(
                        \StoreKeeper\StoreKeeper\Model\OrderSync\Consumer::CONSUMER_NAME,
                        $this->json->serialize(['orderId' => $order->getIncrementId()])
                    );

                    $order->setStorekeeperOrderPendingSync(1);
                    $order->save();
                }
            }
        }
    }
}
