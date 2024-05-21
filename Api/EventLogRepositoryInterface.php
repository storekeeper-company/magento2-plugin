<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface EventLogRepositoryInterface
{

    /**
     * Save EventLog
     * @param \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface $eventLog
     * @return \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface $eventLog
    );

    /**
     * Retrieve EventLog
     * @param string $eventlogId
     * @return \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($eventlogId);

    /**
     * Retrieve EventLog matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magento\Framework\Api\SearchCriteriaInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete EventLog
     * @param \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface $eventLog
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \StoreKeeper\StoreKeeper\Api\Data\EventLogInterface $eventLog
    );

    /**
     * Delete EventLog by ID
     * @param string $eventlogId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($eventlogId);
}

