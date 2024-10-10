<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Generate;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use StoreKeeper\StoreKeeper\Logger\Logger;

class Index extends Action implements HttpGetActionInterface
{
    private PublisherInterface $messagePublisher;
    private Logger $logger;

    /**
     * Products constructor.
     * @param Context $context
     * @param ManagerInterface $messageManager
     * @param PublisherInterface $messagePublisher
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ManagerInterface $messageManager,
        PublisherInterface $messagePublisher,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->messageManager = $messageManager;
        $this->messagePublisher = $messagePublisher;
        $this->logger = $logger;
    }

    /**
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $exportEntity = $this->_request->getParam('export_entity');
            $this->messagePublisher->publish(
                'storekeeper.data.export',
                '{"entity":"' . $exportEntity . '"}'
            );
            $this->messageManager->addSuccessMessage(
                __(
                    'Message is added to queue, wait to get your file soon.'
                    . ' Make sure your cron job is running to export the file'
                )
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->messageManager->addErrorMessage(__('Error during generating export data'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('storekeeper/export/index');

        return $resultRedirect;
    }
}
