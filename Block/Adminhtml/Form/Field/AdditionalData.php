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

class AdditionalData extends \Magento\Framework\Data\Form\Element\AbstractElement
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

        $version = 'unknown';

        $composerFile = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . "composer.json";
        if (file_exists($composerFile)) {
            $composerContents = file_get_contents($composerFile);
            $composerJson = json_decode($composerContents, true);
            if (isset($composerJson['version'])) {
                $version = $composerJson['version'];
            }
        }
        return "
            Version: {$version}
        ";
    }

    
}