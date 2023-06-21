<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Psr\Log\LoggerInterface;
use StoreKeeper\StoreKeeper\Model\Export\CsvFileContent;
use Magento\Framework\Filesystem;

class Consumer
{
    private LoggerInterface $logger;
    private Filesystem $filesystem;
    private CsvFileContent $csvFileContent;

    public function __construct(
        LoggerInterface $logger,
        Filesystem $filesystem,
        CsvFileContent $csvFileContent
    ) {
        $this->logger = $logger;
        $this->filesystem = $filesystem;
        $this->csvFileContent = $csvFileContent;
    }

    /**
     * @param string $request
     * @return void
     */
    public function process($request): void
    {
        try {
            $data = json_decode($request, true);
            $exportEntity = $data['entity'];
            $contents = $this->csvFileContent->getFileContents($exportEntity);
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
            $directory->writeFile('export/' . $this->csvFileContent->getFileName($exportEntity), $contents);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
