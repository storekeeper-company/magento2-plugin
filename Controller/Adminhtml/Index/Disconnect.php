<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Url;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use Magento\Framework\MessageQueue\PublisherInterface;

class Disconnect extends Action implements HttpGetActionInterface
{
    private Auth $authHelper;
    private Url $url;
    private PublisherInterface $publisher;
    private StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Auth $authHelper
     * @param ManagerInterface $messageManager
     * @param Url $url
     * @param PublisherInterface $publisher
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Auth $authHelper,
        ManagerInterface $messageManager,
        Url $url,
        PublisherInterface $publisher,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->authHelper = $authHelper;
        $this->messageManager = $messageManager;
        $this->url = $url;
        $this->publisher = $publisher;
        $this->storeManager = $storeManager;
    }

    /**
     * Create validation exception
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Validate for csrf
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * SK disconnect action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $storeId = $this->getRequest()->getParam('storeId');
            $message = [
                "type" => "disconnect",
                "storeId" => $storeId
            ];

            $this->publisher->publish('storekeeper.queue.events', json_encode($message));
            $this->authHelper->disconnectStore($storeId);
            $this->messageManager->addSuccess(__("Store {$storeId} has been disconnected from StoreKeeper"));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        return $this->_redirect('adminhtml/system_config/edit/section/storekeeper_general', ['store' => $storeId]);
    }

    /**
     * Disconnect action allowed
     *
     * @return true
     */
    protected function _isAllowed()
    {
        return true;
    }
}
