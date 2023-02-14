<?php

namespace StoreKeeper\StoreKeeper\Controller\Adminhtml\Index;

use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class Disconnect extends \Magento\Backend\App\Action implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\Http $request,
        Auth $authHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Url $url
    ) {
        parent::__construct($context);

        $this->request = $request;
        $this->authHelper = $authHelper;
        $this->messageManager = $messageManager;
        $this->url = $url;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $storeId = $this->request->getParam('storeId');
        try {
            $this->authHelper->disconnectStore($storeId);
            $this->messageManager->addSuccess(__("Store {$storeId} has been disconnected from StoreKeeper"));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }

        return $this->_redirect('adminhtml/system_config/edit/section/storekeeper_general', ['store' => $storeId]);
    }

    protected function _isAllowed()
    {
        return true;
    }
}
