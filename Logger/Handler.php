<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = \Monolog\Logger::DEBUG;

    protected $fileName = "/var/log/storekeeper.log";
}
