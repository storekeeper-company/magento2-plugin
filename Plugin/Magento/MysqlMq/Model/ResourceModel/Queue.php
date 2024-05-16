<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Plugin\Magento\MysqlMq\Model\ResourceModel;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\MysqlMq\Model\ResourceModel\Queue as subjectQueue;
use Magento\MysqlMq\Model\ResourceModel\MessageCollectionFactory;
use Magento\MysqlMq\Model\ResourceModel\MessageStatusCollectionFactory;
use StoreKeeper\StoreKeeper\Api\Data\TaskLogInterfaceFactory;
use StoreKeeper\StoreKeeper\Api\TaskLogRepositoryInterface;
use StoreKeeper\StoreKeeper\Model\Consumer as eventConsumer;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use StoreKeeper\StoreKeeper\Model\OrderSync\Consumer as orderConsumer;

class Queue
{
    const SK_MESSAGE_QUEUES = [
        eventConsumer::CONSUMER_NAME,
        orderConsumer::CONSUMER_NAME,
        AbstractExportManager::CONSUMER_NAME
    ];

    private MessageCollectionFactory $messageCollectionFactory;
    private MessageStatusCollectionFactory $messageStatusCollectionFactory;
    private TaskLogRepositoryInterface $taskLogRepository;
    private TaskLogInterfaceFactory $taskLogFactory;
    private TimezoneInterface $timezone;

    /**
     * Constructor
     *
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param MessageStatusCollectionFactory $messageStatusCollectionFactory
     * @param TaskLogInterfaceFactory $taskLogFactory
     * @param TaskLogRepositoryInterface $taskLogRepository
     * @param TimezoneInterface $timezone
     */
    public function __construct (
        MessageCollectionFactory $messageCollectionFactory,
        MessageStatusCollectionFactory $messageStatusCollectionFactory,
        TaskLogInterfaceFactory $taskLogFactory,
        TaskLogRepositoryInterface $taskLogRepository,
        TimezoneInterface $timezone
    ) {
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->messageStatusCollectionFactory = $messageStatusCollectionFactory;
        $this->taskLogFactory = $taskLogFactory;
        $this->taskLogRepository = $taskLogRepository;
        $this->timezone = $timezone;
    }

    /**
     * Get published message info and record into SK Task Log
     *
     * @param subjectQueue $subject
     * @param $result
     * @param $messageId
     * @param $queueNames
     * @return mixed
     */
    public function afterLinkQueues(subjectQueue $subject, $result, $messageId, $queueNames)
    {
        $queueName = reset($queueNames);
        if (in_array($queueName, self::SK_MESSAGE_QUEUES)) {
            $collection = $this->getMessagesCollection($messageId);

            if ($collection->count() > 0) {
                foreach ($collection as $message) {
                    $taskLog = $this->taskLogFactory->create();
                    $taskLog->addData($message->getData());
                    $taskLog->setMessageId($message->getId());
                    $taskLog->setUpdatedAt($this->timezone->date($message->getUpdatedAt())->getTimestamp());

                    $this->taskLogRepository->save($taskLog);
                }
            }
        }

        return $result;
    }

    public function afterTakeMessagesInProgress(subjectQueue $subject, $result, $relationIds)
    {
        if (!empty($relationIds)) {
            $messages = $this->messageStatusCollectionFactory->create();
            $messages->addFieldToFilter('id', ['IN' => $relationIds])
                ->addFieldToSelect('message_id');

            foreach ($messages as $message) {
                $collection = $this->getMessagesCollection($message->getMessageId());
                $this->updateExistingTaskLog($collection, $message->getMessageId());
            }
        }

        return $result;
    }

    public function afterPushBackForRetry(subjectQueue $subject, $result, $relationId)
    {
        $collection = $this->getMessagesCollection($relationId);
        $this->updateExistingTaskLog($collection, $relationId);

        return $result;
    }

    public function afterChangeStatus(subjectQueue $subject, $result, $relationIds)
    {
        if (!empty($relationIds)) {
            $messages = $this->messageStatusCollectionFactory->create();
            $messages->addFieldToFilter('id', ['IN' => $relationIds])
                ->addFieldToSelect('message_id');

            foreach ($messages as $message) {
                $collection = $this->getMessagesCollection($message->getMessageId());
                $this->updateExistingTaskLog($collection, $message->getMessageId());
            }
        }

        return $result;
    }

    private function updateExistingTaskLog($collection, $messageId)
    {
        if ($collection->count() > 0) {
            foreach ($collection as $message) {
                $taskLog = $this->taskLogRepository->getByMessageId($messageId);
                $taskLog->setTopicName($message->getTopicName());
                $taskLog->setBody($message->getBody());
                $taskLog->setUpdatedAt($this->timezone->date($message->getUpdatedAt())->getTimestamp());
                $taskLog->setStatus($message->getStatus());
                $taskLog->setNumberOfTrials($message->getNumberOfTrials());

                $this->taskLogRepository->save($taskLog);
            }
        }
    }

    /**
     * Filter published messages collection by SK topics list and required message_id
     *
     * @param $messageId
     * @return \Magento\MysqlMq\Model\ResourceModel\MessageCollection
     */
    private function getMessagesCollection($messageId) {
        $collection = $this->messageCollectionFactory->create();
        $collection->addFieldToFilter('main_table.id', $messageId);
        $collection->addFieldToFilter('main_table.topic_name', ['IN' => self::SK_MESSAGE_QUEUES]);

        $collection->join(
            ['status' => $collection->getTable('queue_message_status')],
            'main_table.id = status.message_id',
            ['updated_at', 'status', 'number_of_trials']
        );

        return $collection;
    }
}
