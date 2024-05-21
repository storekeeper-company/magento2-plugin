<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use StoreKeeper\StoreKeeper\Api\Data\EventLogInterface;
use StoreKeeper\StoreKeeper\Api\Data\EventLogInterfaceFactory;
use StoreKeeper\StoreKeeper\Api\Data\EventLogSearchResultsInterfaceFactory;
use StoreKeeper\StoreKeeper\Api\EventLogRepositoryInterface;
use StoreKeeper\StoreKeeper\Model\ResourceModel\EventLog as ResourceEventLog;
use StoreKeeper\StoreKeeper\Model\ResourceModel\EventLog\CollectionFactory as EventLogCollectionFactory;

class EventLogRepository implements EventLogRepositoryInterface
{
    protected EventLogInterfaceFactory $eventLogFactory;
    protected CollectionProcessorInterface $collectionProcessor;
    protected ResourceEventLog $resource;
    protected EventLogCollectionFactory $eventLogCollectionFactory;
    protected EventLog $searchResultsFactory;

    /**
     * @param ResourceEventLog $resource
     * @param EventLogInterfaceFactory $eventLogFactory
     * @param EventLogCollectionFactory $eventLogCollectionFactory
     * @param EventLogSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        ResourceEventLog $resource,
        EventLogInterfaceFactory $eventLogFactory,
        EventLogCollectionFactory $eventLogCollectionFactory,
        EventLogSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->eventLogFactory = $eventLogFactory;
        $this->eventLogCollectionFactory = $eventLogCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(EventLogInterface $eventLog)
    {
        try {
            $this->resource->save($eventLog);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Event Log: %1',
                $exception->getMessage()),
                $exception
            );
        }
        return $eventLog;
    }

    /**
     * @inheritDoc
     */
    public function get($eventLogId)
    {
        $eventLog = $this->eventLogFactory->create();
        $this->resource->load($eventLog, $eventLogId);
        if (!$eventLog->getId()) {
            throw new NoSuchEntityException(__('Event Log with id "%1" does not exist.', $eventLogId));
        }
        return $eventLog;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->eventLogCollectionFactory->create();

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
    public function delete(EventLogInterface $eventLog)
    {
        try {
            $eventLogModel = $this->eventLogFactory->create();
            $this->resource->load($eventLogModel, $eventLog->getEventlogId());
            $this->resource->delete($eventLogModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Event Log: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($eventLogId)
    {
        return $this->delete($this->get($eventLogId));
    }
}

