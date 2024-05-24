<?php

namespace StoreKeeper\StoreKeeper\Logger;

class Logger extends \Monolog\Logger
{
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
