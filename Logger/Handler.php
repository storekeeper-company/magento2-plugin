<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $loggerType = \Monolog\Logger::DEBUG;

    protected $fileName = "/var/log/storekeeper.log";

    /**
     * @param \Throwable $e
     * @return array
     */
    public function buildReportData(\Throwable $e): array
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
