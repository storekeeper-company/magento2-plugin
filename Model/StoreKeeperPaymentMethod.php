<?php

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Payment\Model\Method\AbstractMethod;

class StoreKeeperPaymentMethod extends AbstractMethod
{
    protected $_code = 'storekeeperpayment';

    protected $_canAuthorize = true;

    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
//        $this->logger->debug(print_r($payment->getOrder()->getData(),true));
        file_put_contents('/var/www/test.log', 'test1', FILE_APPEND);
        file_put_contents('/var/www/order.log', print_r($payment->getOrder()->getData(), true), FILE_APPEND);
        file_put_contents('/var/www/order.log', $payment->getOrder()->getData(), FILE_APPEND);
//        $this->logger->debug($amount, true);
//        die();
        return $this;
    }

    public function order(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
//        var_dump($amount);
        file_put_contents('/var/www/test1.log', 'test2', FILE_APPEND);
        file_put_contents('/var/www/test1.log', $payment->getOrder()->getData(), FILE_APPEND);
//        die();
        return $this;
    }
}
