<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use StoreKeeper\StoreKeeper\Api\Data\TaskLogInterface;
use StoreKeeper\StoreKeeper\Api\Data\TaskLogInterfaceFactory;
use StoreKeeper\StoreKeeper\Api\Data\TaskLogSearchResultsInterfaceFactory;
use StoreKeeper\StoreKeeper\Api\TaskLogRepositoryInterface;
use StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog as ResourceTaskLog;
use StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog\CollectionFactory as TaskLogCollectionFactory;

class TaskLogRepository implements TaskLogRepositoryInterface
{

    /**
     * @var TaskLogInterfaceFactory
     */
    protected $taskLogFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var TaskLog
     */
    protected $searchResultsFactory;

    /**
     * @var TaskLogCollectionFactory
     */
    protected $taskLogCollectionFactory;

    /**
     * @var ResourceTaskLog
     */
    protected $resource;


    /**
     * @param ResourceTaskLog $resource
     * @param TaskLogInterfaceFactory $taskLogFactory
     * @param TaskLogCollectionFactory $taskLogCollectionFactory
     * @param TaskLogSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceTaskLog $resource,
        TaskLogInterfaceFactory $taskLogFactory,
        TaskLogCollectionFactory $taskLogCollectionFactory,
        TaskLogSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->taskLogFactory = $taskLogFactory;
        $this->taskLogCollectionFactory = $taskLogCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(TaskLogInterface $taskLog)
    {
        try {
            $this->resource->save($taskLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the taskLog: %1',
                $exception->getMessage()),
                $exception
            );
        }
        return $taskLog;
    }

    /**
     * @inheritDoc
     */
    public function get($taskLogId)
    {
        $taskLog = $this->taskLogFactory->create();
        $this->resource->load($taskLog, $taskLogId);
        if (!$taskLog->getId()) {
            throw new NoSuchEntityException(__('TaskLog with id "%1" does not exist.', $taskLogId));
        }
        return $taskLog;
    }

    /**
     * @param $messageId
     * @return TaskLogInterface
     * @throws NoSuchEntityException
     */
    public function getByMessageId($messageId)
    {
        $taskLog = $this->taskLogFactory->create();
        $this->resource->load($taskLog, $messageId, 'message_id');
        if (!$taskLog->getId()) {
            throw new NoSuchEntityException(__('TaskLog with message id "%1" does not exist.', $taskLogId));
        }
        return $taskLog;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->taskLogCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(TaskLogInterface $taskLog)
    {
        try {
            $taskLogModel = $this->taskLogFactory->create();
            $this->resource->load($taskLogModel, $taskLog->getTasklogId());
            $this->resource->delete($taskLogModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the TaskLog: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($taskLogId)
    {
        return $this->delete($this->get($taskLogId));
    }
}
