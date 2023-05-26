<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Backend\Model\Url;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class RefreshStore extends AbstractElement
{
    private Auth $authHelper;
    private Http $request;
    private Url $backendUrlManager;

    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        ?SecureHtmlRenderer $secureRenderer,
        ?Random $random,
        Auth $authHelper,
        Http $request,
        Url $backendUrlManager,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->request = $request;
        $this->backendUrlManager = $backendUrlManager;
    }

    public function getElementHtml()
    {
        $storeId = $this->request->getParam('store');

        if ($this->authHelper->isConnected($storeId)) {
            $url = $this->backendUrlManager->getUrl('storekeeper/index/index', ['storeId' => $storeId]);
            return "<a href='{$url}'>" . __("Refresh store information") . "</a>";
        }
    }
}
