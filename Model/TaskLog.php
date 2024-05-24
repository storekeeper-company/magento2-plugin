<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Framework\Model\AbstractModel;
use StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface;

class TaskLog extends AbstractModel implements TaskLogInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog::class);
    }

    /**
     * @inheritDoc
     */
    public function getTasklogId()
    {
        return $this->getData(self::TASKLOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTasklogId($tasklogId)
    {
        return $this->setData(self::TASKLOG_ID, $tasklogId);
    }

    /**
     * @inheritDoc
     */
    public function getMessageId()
    {
        return $this->getData(self::MESSAGE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMessageId($messageId)
    {
        return $this->setData(self::MESSAGE_ID, $messageId);
    }

    /**
     * @inheritDoc
     */
    public function getTopicName()
    {
        return $this->getData(self::TOPIC_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setTopicName($topicName)
    {
        return $this->setData(self::TOPIC_NAME, $topicName);
    }

    /**
     * @inheritDoc
     */
    public function getBody()
    {
        return $this->getData(self::BODY);
    }

    /**
     * @inheritDoc
     */
    public function setBody($body)
    {
        return $this->setData(self::BODY, $body);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getNumberOfTrials()
    {
        return $this->getData(self::NUMBER_OF_TRIALS);
    }

    /**
     * @inheritDoc
     */
    public function setNumberOfTrials($numberOfTrials)
    {
        return $this->setData(self::NUMBER_OF_TRIALS, $numberOfTrials);
    }
}
