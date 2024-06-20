<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Form\Field;

use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class AdditionalData extends AbstractElement
{
    private Auth $authHelper;
    private Http $request;

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
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
        $this->authHelper = $authHelper;
        $this->request = $request;
    }

    /**
     * Get composer version
     *
     * @return string
     */
    public function getElementHtml()
    {
        $version = 'unknown';

        $composerFile = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . "composer.json";
        if (file_exists($composerFile)) {
            $composerContents = file_get_contents($composerFile);
            $composerJson = json_decode($composerContents, true);
            if (isset($composerJson['version'])) {
                $version = $composerJson['version'];
            }
        }
        return $version;
    }
}
