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

class ShopId extends \Magento\Framework\Data\Form\Element\AbstractElement
{
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        ?SecureHtmlRenderer $secureRenderer,
        ?Random $random,
        Auth $authHelper,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    public function getElementHtml()
    {
        $shopId = null;

        if ($storekeeperStoreInformation = $this->scopeConfig->getValue(
            'storekeeper_general/general/storekeeper_store_information',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $this->request->getParam('store')
        )) {
            if ($storekeeperStoreInformation = json_decode($storekeeperStoreInformation, true)) {
                $shopId = $storekeeperStoreInformation['shop']['id'] ?? null;
            }
        }

        return "<input type='text' class='input-text admin__control-text' value='{$shopId}' readonly />";
    }
}
