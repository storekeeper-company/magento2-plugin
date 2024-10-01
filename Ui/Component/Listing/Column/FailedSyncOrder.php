<?php

namespace StoreKeeper\StoreKeeper\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class FailedSyncOrder extends Column
{
    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (array_key_exists('is_failed', $item)) {
                    $item['is_failed'] = !$item['is_failed'] ? __('No') : __('Yes');
                }
            }
        }

        return $dataSource;
    }
}
