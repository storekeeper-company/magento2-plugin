<?php

namespace StoreKeeper\StoreKeeper\Model;

class TaxCalculation extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Tax\Model\ResourceModel\Calculation::class);
    }
}
