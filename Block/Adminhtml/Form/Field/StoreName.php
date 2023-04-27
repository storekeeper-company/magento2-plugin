<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class StoreName extends AbstractElement
{
    private Auth $authHelper;
    private Http $request;
    private ScopeConfigInterface $scopeConfig;

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
     * @param ScopeConfigInterface $scopeConfig
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
        ScopeConfigInterface $scopeConfig,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Store name
     *
     * @return string
     */
    public function getElementHtml()
    {
        $storeName = null;

        if ($storekeeperStoreInformation = $this->scopeConfig->getValue(
            'storekeeper_general/general/storekeeper_store_information',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $this->request->getParam('store')
        )) {
            if ($storekeeperStoreInformation = json_decode($storekeeperStoreInformation, true)) {
                $storeName = $storekeeperStoreInformation['shop']['site']['title'] ?? null;
            }
        }

        return "<input type='text' class='input-text admin__control-text' value='{$storeName}' readonly />";
    }
}
