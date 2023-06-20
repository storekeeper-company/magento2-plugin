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
            $exportEntity = '';
            $data = json_decode($request, true);
            if ($data['entity'] == CsvFileContent::CATALOG_PRODUCT_ENTITY) {
                $contents = $this->csvFileContent->getFileContents(CsvFileContent::CATALOG_PRODUCT_ENTITY);
                $exportEntity = CsvFileContent::CATALOG_PRODUCT_ENTITY;
            }
            if ($data['entity'] == CsvFileContent::CUSTOMER_ENTITY) {
                $contents = $this->csvFileContent->getFileContents(CsvFileContent::CUSTOMER_ENTITY);
                $exportEntity = CsvFileContent::CUSTOMER_ENTITY;
            }
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
            $directory->writeFile('export/' . $this->csvFileContent->getFileName($exportEntity), $contents);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
