<?php

namespace StoreKeeper\StoreKeeper\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = \Monolog\Logger::DEBUG;

    protected $fileName = "/var/log/storekeeper.log";

    public function buildReportData(\Throwable $e)
    {
        $data = [];
        if (!is_null($e)) {
            $data = [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'mes' => $e->getMessage(),
                'file' => $e->getFile(),
                'class' => get_class($e),
            ];
            $pe = $e->getPrevious();
            if ($pe) {
                $data['previous'] = self::buildReportData($pe);
            }
        }

        return $data;
    }
}
