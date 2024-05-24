<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Api\Data;

interface TaskLogInterface
{

    const TOPIC_NAME = 'topic_name';
    const UPDATED_AT = 'updated_at';
    const TASKLOG_ID = 'tasklog_id';
    const NUMBER_OF_TRIALS = 'number_of_trials';
    const BODY = 'body';
    const MESSAGE_ID = 'message_id';
    const STATUS = 'status';

    /**
     * Get tasklog_id
     * @return string|null
     */
    public function getTasklogId();

    /**
     * Set tasklog_id
     * @param string $tasklogId
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setTasklogId($tasklogId);

    /**
     * Get message_id
     * @return string|null
     */
    public function getMessageId();

    /**
     * Set message_id
     * @param string $messageId
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setMessageId($messageId);

    /**
     * Get topic_name
     * @return string|null
     */
    public function getTopicName();

    /**
     * Set topic_name
     * @param string $topicName
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setTopicName($topicName);

    /**
     * Get body
     * @return string|null
     */
    public function getBody();

    /**
     * Set body
     * @param string $body
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setBody($body);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setStatus($status);

    /**
     * Get number_of_trials
     * @return string|null
     */
    public function getNumberOfTrials();

    /**
     * Set number_of_trials
     * @param string $numberOfTrials
     * @return \StoreKeeper\StoreKeeper\TaskLog\Api\Data\TaskLogInterface
     */
    public function setNumberOfTrials($numberOfTrials);
}
