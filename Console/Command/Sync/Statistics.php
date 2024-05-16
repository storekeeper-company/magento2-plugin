<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\MysqlMq\Model\QueueManagement;
use StoreKeeper\StoreKeeper\Model\ResourceModel\EventLog\CollectionFactory as EventLogCollectionFactory;
use StoreKeeper\StoreKeeper\Model\ResourceModel\TaskLog\CollectionFactory as TaskLogCollectionFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Statistics extends Command
{
    private TaskLogCollectionFactory $taskLogCollectionFactory;
    private EventLogCollectionFactory $eventLogCollectionFactory;
    private TimezoneInterface $timezone;

    public function __construct(
        EventLogCollectionFactory $eventLogCollectionFactory,
        TaskLogCollectionFactory $taskLogCollectionFactory,
        TimezoneInterface $timezone
    ) {
        parent::__construct();

        $this->taskLogCollectionFactory = $taskLogCollectionFactory;
        $this->eventLogCollectionFactory = $eventLogCollectionFactory;
        $this->timezone = $timezone;
    }


    protected function configure(): void
    {
        $this->setName('storekeeper:magento2-plugin:sync-statistics')
            ->setDescription('Output Storekeeeper Sync statistics');
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;

        try {
            $output->writeln('<info>StoreKeeper Sync statistics</info>');
            $output->writeln('');
            $output->writeln('<info>Tasks in queue: ' . $this->tasksInQueueCount() . '</info>');
            $output->writeln('<info>Webhooks log count: ' . $this->eventLogCount() . '</info>');
            $output->writeln('<info>Last task processed: ' . $this->getLastProcessedTaskTime() . '</info>');
            $output->writeln('<info>Last webhook received: ' . $this->getLastWebhookEventTime() . '</info>');

        } catch (LocalizedException $e) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $e->getMessage()
            ));
            $exitCode = 1;
        }

        return $exitCode;
    }

    /**
     * Get Sk-related tasks in 'New' and 'Retry required' statuses
     *
     * @return int
     * @throws LocalizedException
     */
    public function tasksInQueueCount()
    {
        $taskLogCollection = $this->taskLogCollectionFactory->create();
        $taskLogCollection->addFieldToFilter(
            'status',
            ['IN' => [QueueManagement::MESSAGE_STATUS_NEW, QueueManagement::MESSAGE_STATUS_RETRY_REQUIRED]]
        );

        return $taskLogCollection->getSize();
    }

    /**
     * @return int
     */
    public function eventLogCount():int
    {
        $eventLogCollection = $this->eventLogCollectionFactory->create();

        return $eventLogCollection->getSize();
    }

    /**
     * @return string
     */
    public function getLastProcessedTaskTime(): string
    {
        $taskLogCollection = $this->taskLogCollectionFactory->create();
        $taskLogCollection->addFieldToSelect('updated_at');
        $taskLogCollection->addOrder('updated_at', SortOrder::SORT_DESC);
        $taskLogCollection->setPageSize(1);

        $latestItem = $taskLogCollection->getFirstItem();

        return ($latestItem->getUpdatedAt()) ? $latestItem->getUpdatedAt() : '0 tasks processed';
    }

    /**
     * @return string
     */
    public function getLastWebhookEventTime(): string
    {
        $eventLogCollection = $this->eventLogCollectionFactory->create();
        $eventLogCollection->addFieldToSelect('date');
        $eventLogCollection->addOrder('date', SortOrder::SORT_DESC);
        $eventLogCollection->setPageSize(1);

        $latestItem = $eventLogCollection->getFirstItem();

        return ($latestItem->getDate()) ? $latestItem->getDate() : '0 webhooks received';
    }
}
