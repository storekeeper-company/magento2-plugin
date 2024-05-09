<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Console\Command\Sync;

use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Statistics extends Command
{
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
            $output->writeln('<info>StoreKeeper Sync statistics:</info>');

        } catch (LocalizedException $e) {
            $output->writeln(sprintf(
                '<error>%s</error>',
                $e->getMessage()
            ));
            $exitCode = 1;
        }

        return $exitCode;
    }
}
