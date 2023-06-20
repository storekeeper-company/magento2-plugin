<?php

namespace StoreKeeper\StoreKeeper\Model\ResourceModel\TaxCalculation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \StoreKeeper\StoreKeeper\Model\TaxCalculation::class,
            \Magento\Tax\Model\ResourceModel\Calculation::class
        );
    }
}
