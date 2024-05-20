<?php

namespace StoreKeeper\StoreKeeper\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Status extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item['status'] = $this->matchStatus($item['status']);
            }
        }

        return $dataSource;
    }

    /**
     * Match message queue status code to string label
     * String labels taken from constants in core class Magento\MysqlMq\Model\QueueManagement
     *
     * @param int $status
     * @return string
     */
    private function matchStatus(int $status): string
    {
        return match ($status) {
            2 => __('New'),
            3 => __('In Progress'),
            4 => __('Complete'),
            5 => __('Retry required'),
            6 => __('Error'),
            7 => __('To be deleted'),
            default => __('Unknown status: %1', $status)
        };
    }
}
