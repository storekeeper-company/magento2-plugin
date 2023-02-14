<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class AuthKey extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        ?SecureHtmlRenderer $secureRenderer,
        ?Random $random,
        Auth $authHelper,
        \Magento\Framework\App\Request\Http $request,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->request = $request;
    }

    public function getElementHtml()
    {
        return "
            <span style='word-break: break-all'>" . $this->authHelper->authCheck($this->request->getParam('store')) . "</span>
        ";
    }
}
