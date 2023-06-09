<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Model\Url;

class SettingsButton extends Field
{
    private Url $backendUrlManager;

    public function __construct(
        Context $context,
        Url $backendUrlManager
    ) {
        parent::__construct($context);
        $this->backendUrlManager = $backendUrlManager;
    }

    /**
     * Render block: extension version
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $redirectUrl = $this->backendUrlManager->getUrl('storekeeper/export/index');
        $text = __('StoreKeeper Export Data page has been moved. Please, ') . ' <a href="' . $redirectUrl . '">' . __('click the link') . '</a>' .  __(' to go to the new export data page.');
        $html = '<tr id="row_' . $element->getHtmlId() . '">';
        $html .= '  <td class="value" style="width:100%;">' . $text . '</td>';
        $html .= '</tr>';

        return $html;
    }
}
