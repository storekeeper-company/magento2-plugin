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
     * @param $status
     * @return string
     */
    private function matchStatus($status)
    {
        return match ((int)$status) {
            2 => 'New',
            3 => 'In Progress',
            4 => 'Complete',
            5 => 'Retry required',
            6 => 'Error',
            7 => 'To be deleted',
            default => '',
        };
    }
}
