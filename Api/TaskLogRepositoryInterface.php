<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface TaskLogRepositoryInterface
{

    /**
     * Save TaskLog
     * @param \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface $taskLog
     * @return \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface $taskLog
    );

    /**
     * Retrieve TaskLog
     * @param string $tasklogId
     * @return \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($tasklogId);

    /**
     * Retrieve TaskLog matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \StoreKeeper\StoreKeeper\Api\Data\TaskLogSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete TaskLog
     * @param \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface $taskLog
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface $taskLog
    );

    /**
     * Delete TaskLog by ID
     * @param string $tasklogId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($tasklogId);
}
