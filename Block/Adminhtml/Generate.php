<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Model\Url;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;

class Generate extends Template
{
    private Url $backendUrlManager;

    /**
     * Generate constructor.
     * @param Context $context
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     * @param Url $backendUrlManager
     */
    public function __construct(
        Context $context,
        Url $backendUrlManager,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null,
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->backendUrlManager = $backendUrlManager;
    }

    /**
     * @param string $exportEntity
     * @return string
     */
    public function getGenerateUrl(string $exportEntity): string
    {
        return $this->backendUrlManager->getUrl('storekeeper/generate/index', ['export_entity' => $exportEntity]);
    }
}
