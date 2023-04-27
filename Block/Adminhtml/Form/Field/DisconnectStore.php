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

class DisconnectStore extends AbstractElement
{
    private Auth $authHelper;
    private Http $request;
    private Url $backendUrlManager;

    /**
     * Constructor
     *
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param SecureHtmlRenderer|null $secureRenderer
     * @param Random|null $random
     * @param Auth $authHelper
     * @param Http $request
     * @param Url $backendUrlManager
     * @param $data
     */
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

    /**
     * Get disconnect button
     *
     * @return string|void
     */
    public function getElementHtml()
    {
        $storeId = $this->request->getParam('store');
        if ($this->authHelper->isConnected($storeId)) {
            $url = $this->backendUrlManager->getUrl('storekeeper/index/disconnect', ['storeId' =>$this->request->getParam('store')]);
            return "<a href='{$url}' class='action-default'>" . __("Disconnect from StoreKeeper") . "</a>";
        }
    }
}
