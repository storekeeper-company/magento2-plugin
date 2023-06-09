<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class Products extends Action implements HttpGetActionInterface
{
    private PublisherInterface $messagePublisher;

    /**
     * Products constructor.
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param PublisherInterface $messagePublisher
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        PublisherInterface $messagePublisher
    ) {
        parent::__construct($context);
        $this->messageManager = $messageManager;
        $this->messagePublisher = $messagePublisher;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $this->messagePublisher->publish(
                'storekeeper.data.export',
                '{"entity":"catalog_product"}'
            );
            $this->messageManager->addSuccessMessage(
                __(
                    'Message is added to queue, wait to get your file soon.'
                    . ' Make sure your cron job is running to export the file'
                )
            );
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->messageManager->addErrorMessage(__('Please correct the data sent value.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('storekeeper/export/index');

        return $resultRedirect;
    }
}
