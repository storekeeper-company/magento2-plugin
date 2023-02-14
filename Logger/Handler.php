<?php

namespace StoreKeeper\StoreKeeper\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = \Monolog\Logger::DEBUG;

    protected $fileName = "/var/log/storekeeper.log";
}
