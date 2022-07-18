<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\AbstractForm;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\CollectionFactory;

use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class RefreshStore extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        ?SecureHtmlRenderer $secureRenderer,
        ?Random $random,
        Auth $authHelper,
        \Magento\Framework\App\Request\Http $request,
        $data = [],
        \Magento\Backend\Model\Url $backendUrlManager
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->request = $request;
        $this->backendUrlManager = $backendUrlManager;
    }

    public function getElementHtml()
    {
        $url = $this->backendUrlManager->getUrl('storekeeper/index/index', ['storeId' =>$this->request->getParam('store')]);
        return "<a href='{$url}'>Refresh store information</a>";
    }
}
