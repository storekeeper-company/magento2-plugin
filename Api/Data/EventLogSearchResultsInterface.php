<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api\Data;

interface EventLogSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get EventLog list.
     * @return \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface[]
     */
    public function getItems();

    /**
     * Set request_route list.
     * @param \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

