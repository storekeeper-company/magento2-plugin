<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class ShopId extends AbstractElement
{
    private Auth $authHelper;
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
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param $data
     */
    public function __construct(
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        ?SecureHtmlRenderer $secureRenderer,
        ?Random $random,
        Auth $authHelper,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get Shop ID
     *
     * @return string
     */
    public function getElementHtml()
    {
        $shopId = null;

        if ($storekeeperStoreInformation = $this->scopeConfig->getValue(
            'storekeeper_general/general/storekeeper_store_information',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES,
            $this->storeManager->getStore()->getId()
        )) {
            if ($storekeeperStoreInformation = json_decode($storekeeperStoreInformation, true)) {
                $shopId = $storekeeperStoreInformation['shop']['id'] ?? null;
            }
        }

        return "<input type='text' class='input-text admin__control-text' value='{$shopId}' readonly />";
    }
}
