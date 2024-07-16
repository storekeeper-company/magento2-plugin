<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Plugin\Magento\MysqlMq\Model\ResourceModel;

use Magento\MysqlMq\Model\ResourceModel\Queue as subjectQueue;
use Magento\MysqlMq\Model\ResourceModel\MessageCollectionFactory;
use Magento\MysqlMq\Model\ResourceModel\MessageStatusCollectionFactory;
use StoreKeeper\StoreKeeper\Api\Data\TaskLogInterfaceFactory;
use StoreKeeper\StoreKeeper\Api\TaskLogRepositoryInterface;
use StoreKeeper\StoreKeeper\Model\Consumer as EventConsumer;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use StoreKeeper\StoreKeeper\Model\OrderSync\Consumer as OrderConsumer;

class Queue
{
    const SK_MESSAGE_QUEUES = [
        EventConsumer::CONSUMER_NAME,
        OrderConsumer::CONSUMER_NAME,
        AbstractExportManager::CONSUMER_NAME
    ];

    private MessageCollectionFactory $messageCollectionFactory;
    private MessageStatusCollectionFactory $messageStatusCollectionFactory;
    private TaskLogRepositoryInterface $taskLogRepository;
    private TaskLogInterfaceFactory $taskLogFactory;

    /**
     * Constructor
     *
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param MessageStatusCollectionFactory $messageStatusCollectionFactory
     * @param TaskLogInterfaceFactory $taskLogFactory
     * @param TaskLogRepositoryInterface $taskLogRepository
     */
    public function __construct (
        MessageCollectionFactory $messageCollectionFactory,
        MessageStatusCollectionFactory $messageStatusCollectionFactory,
        TaskLogInterfaceFactory $taskLogFactory,
        TaskLogRepositoryInterface $taskLogRepository
    ) {
        $this->messageCollectionFactory = $messageCollectionFactory;
        $this->messageStatusCollectionFactory = $messageStatusCollectionFactory;
        $this->taskLogFactory = $taskLogFactory;
        $this->taskLogRepository = $taskLogRepository;
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
        foreach ($queueNames as $queueName) {
            if (in_array($queueName, self::SK_MESSAGE_QUEUES)) {
                $collection = $this->getMessagesCollection($messageId);

                if ($collection->count() > 0) {
                    foreach ($collection as $message) {
                        $taskLog = $this->taskLogFactory->create();
                        $taskLog->addData($message->getData());
                        $taskLog->setMessageId($message->getId());
                        $taskLog->setUpdatedAt($this->dateTime->gmtDate());

                        $this->taskLogRepository->save($taskLog);
                    }
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
                $taskLog->setUpdatedAt($this->dateTime->gmtDate());
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
