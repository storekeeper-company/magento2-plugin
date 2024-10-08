<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Backend\Model\Url;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;
use StoreKeeper\StoreKeeper\Helper\Config;

class RefreshStore extends AbstractElement
{
    private Auth $authHelper;
    private Url $backendUrlManager;
    private StoreManagerInterface $storeManager;

    /**
     * Constructor
     *
     * @param Factory $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper $escaper
     * @param SecureHtmlRenderer|null $secureRenderer
     * @param Random|null $random
     * @param Auth $authHelper
     * @param Url $backendUrlManager
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
        Url $backendUrlManager,
        StoreManagerInterface $storeManager,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->backendUrlManager = $backendUrlManager;
        $this->storeManager = $storeManager;
    }

    public function getElementHtml()
    {
        $storeId = $this->authHelper->getStoreId($this->storeManager->getStore()->getId());

        if ($this->authHelper->isConnected($storeId)) {
            $url = $this->backendUrlManager->getUrl('storekeeper/index/index', ['storeId' => $storeId]);
            return "<a href='{$url}'>" . __("Refresh store information") . "</a>";
        }
    }
}
