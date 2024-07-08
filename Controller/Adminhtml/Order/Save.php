<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;

class Save extends Action
{
    protected OrderRepository $orderRepository;
    protected OrderResource $orderResource;

    /**
     * Constructor
     *
     * @param Action\Context $context
     * @param OrderRepository $orderRepository
     * @param OrderResource $orderResource
     */
    public function __construct(
        Action\Context $context,
        OrderRepository $orderRepository,
        OrderResource $orderResource
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->orderResource = $orderResource;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $orderDetached = $this->getRequest()->getParam('order_detached');

        try {
            $order = $this->orderRepository->get($orderId);
            $order->setData('order_detached', $orderDetached);
            $this->orderResource->saveAttribute($order, 'order_detached');

            $this->messageManager->addSuccessMessage(__('Order detached value has been saved.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error saving order detached value.'));
        }

        $this->_redirect('sales/order/view', ['order_id' => $orderId]);
    }
}
