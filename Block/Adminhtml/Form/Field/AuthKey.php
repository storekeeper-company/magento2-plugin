<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class AuthKey extends AbstractElement
{
    private Auth $authHelper;
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
        StoreManagerInterface $storeManager,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Check StoreKeeper auth
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        $storeId = $this->storeManager->getStore()->getId();

        return "<span style='word-break: break-all'>" . $this->authHelper->authCheck($storeId) . "</span>";
    }
}
