<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api\Data;

interface TaskLogSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get TaskLog list.
     * @return \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface[]
     */
    public function getItems();

    /**
     * Set topic_name list.
     * @param \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
