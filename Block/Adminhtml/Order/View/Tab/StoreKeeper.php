<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Order\View\Tab;

/**
 * Order Invoices grid
 *
 * @api
 * @since 100.0.2
 */
class StoreKeeper extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = "StoreKeeper_StoreKeeper::storekeeper/order/view/tab/storekeeper.phtml";
    /**
     * @var \Magento\Framework\Registry
     */
    private $_coreRegistry;
 
    /**
     * View constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
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
 
    /**
     * Retrieve order model instance
     * 
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
    /**
     * Retrieve order model instance
     *
     * @return int
     *Get current id order
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }
 
    /**
     * Retrieve order increment id
     *
     * @return string
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Storekeeper');
    }
 
    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('My Custom Tab');
    }
 
    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }
 
    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }
}
