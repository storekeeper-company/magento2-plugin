<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Order\View\Tab;

class StoreKeeper extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = "StoreKeeper_StoreKeeper::storekeeper/order/view/tab/storekeeper.phtml";

    private $_coreRegistry;
 
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \StoreKeeper\StoreKeeper\Helper\Config $config,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->config = $config;
    }

    public function getStorekeeperBackofficeOrderUrl($order) {
        $sync_auth = $this->config->getSyncAuth($order->getStoreId());
        if ($sync_auth && $json = @json_decode($sync_auth, true)) {
            return "https://{$json['account']}.storekeepercloud.com/#order/details/{$order->getStorekeeperId()}";
        }
        return null;
    }
 
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }
 
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }

    public function getTabLabel()
    {
        return __('Storekeeper');
    }
 
    public function getTabTitle()
    {
        return __('My Custom Tab');
    }
 
    public function canShowTab()
    {
        return true;
    }
 
    public function isHidden()
    {
        return false;
    }
}
