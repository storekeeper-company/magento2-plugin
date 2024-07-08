<?php

namespace StoreKeeper\StoreKeeper\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use StoreKeeper\StoreKeeper\Helper\Config;

class StoreKeeper extends Template implements TabInterface
{
    protected $_template = "StoreKeeper_StoreKeeper::storekeeper/order/view/tab/storekeeper.phtml";
    private Registry $_coreRegistry;
    private Config $config;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param Config $config
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Config $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->config = $config;
    }

    /**
     * Get SK backoffice url
     *
     * @param $order
     * @return string|null
     */
    public function getStorekeeperBackofficeOrderUrl($order)
    {
        $sync_auth = $this->config->getSyncAuth($order->getStoreId());
        if ($sync_auth && $json = @json_decode($sync_auth, true)) {
            return "https://{$json['account']}.storekeepercloud.com/#order/details/{$order->getStorekeeperId()}";
        }
        return null;
    }

    /**
     * Get current order
     *
     * @return mixed|null
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * Get order ID
     *
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }

    /**
     * Get Order Increment ID
     *
     * @return mixed
     */
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }


    /**
     * @return bool
     */
    public function getOrderDetached(): bool
    {
        return $this->getOrder()->getOrderDetached();
    }

    /**
     * @return string
     */
    public function getSaveUrl(): string
    {
        return $this->getUrl('storekeeper/order/save', ['order_id' => $this->getOrder()->getId()]);
    }

    /**
     * Get SK label
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabLabel()
    {
        return __('Storekeeper');
    }

    /**
     * Get Tab Title
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getTabTitle()
    {
        return __('StoreKeeper Order Data');
    }

    /**
     * Can show tab
     * @return true
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * Is Hidden
     *
     * @return false
     */
    public function isHidden()
    {
        return false;
    }
}
