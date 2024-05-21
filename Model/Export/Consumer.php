<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use StoreKeeper\StoreKeeper\Logger\Logger;
use Magento\Framework\Filesystem;

class Consumer
{
    private Logger $logger;
    private Filesystem $filesystem;
    private CsvFileContent $csvFileContent;

    public function __construct(
        Logger $logger,
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
            if ($exportEntity == CsvFileContent::FULL_EXPORT) {
                foreach ($this->csvFileContent->getAllExportEntityTypes() as $exportEntityType) {
                    $this->exportEntityToCsv($exportEntityType);
                }
            } else {
                $this->exportEntityToCsv($exportEntity);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Error while processing export',
                ['error' => $this->logger->buildReportData($e), 'entitty' => $exportEntity]
            );
            $this->logger->error($e->getTraceAsString());
        }
    }

    /**
     * @param string $exportEntity
     */
    private function exportEntityToCsv(string $exportEntity): void
    {
        $contents = $this->csvFileContent->getFileContents($exportEntity);
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $directory->writeFile('export/' . $this->csvFileContent->getFileName($exportEntity), $contents);
    }
}
