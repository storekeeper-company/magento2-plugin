<?php

namespace StoreKeeper\StoreKeeper\Ui\DataProvider;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\Directory\WriteInterface;

/**
 * Data provider for export grid.
 */
class ExportFileDataProvider extends DataProvider
{
    /**
     * @var File|null
     */
    private $fileIO;

    /**
     * @var WriteInterface
     */
    private $directory;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\Framework\Api\Search\ReportingInterface $reporting
     * @param \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param Filesystem $filesystem
     * @param File|null $fileIO
     * @param array $meta
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        \Magento\Framework\Api\Search\ReportingInterface $reporting,
        \Magento\Framework\Api\Search\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        Filesystem $filesystem,
        File $fileIO = null,
        array $meta = [],
        array $data = []
    ) {
        $this->fileSystem = $filesystem;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );

        $this->fileIO = $fileIO ?: ObjectManager::getInstance()->get(File::class);
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
    }

    /**
     * Returns data for grid.
     *
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getData()
    {
        $emptyResponse = ['items' => [], 'totalRecords' => 0];
        if (!$this->directory->isExist($this->directory->getAbsolutePath() . 'export/')) {
            return $emptyResponse;
        }

        $files = $this->getExportFiles($this->directory->getAbsolutePath() . 'export/');
        if (empty($files)) {
            return $emptyResponse;
        }
        $result = [];
        foreach ($files as $file) {
            $path = $this->getPathToExportFile($this->fileIO->getPathInfo($file));
            if ($path) {
                $result['items'][]['file_name'] = $path;
            }
        }

        $paging = $this->request->getParam('paging');
        $pageSize = (int) ($paging['pageSize'] ?? 0);
        $pageCurrent = (int) ($paging['current'] ?? 0);
        $pageOffset = ($pageCurrent - 1) * $pageSize;
        $result['totalRecords'] = count($result['items']);
        $result['items'] = array_slice($result['items'], $pageOffset, $pageSize);

        return $result;
    }

    /**
     * Return relative export file path after "var/export"
     *
     * @param mixed $file
     * @return ?string
     */
    private function getPathToExportFile($file): ?string
    {
        if (strpos($file['filename'], 'storekeeper_') !== false) {
            $delimiter = '/';
            $cutPath = explode(
                $delimiter,
                $this->directory->getAbsolutePath() . 'export'
            );

            $filePath = explode(
                $delimiter,
                $file['dirname'] ?? ''
            );

            return ltrim(
                implode($delimiter, array_diff($filePath, $cutPath)) . $delimiter . $file['basename'],
                $delimiter
            );
        }

        return null;
    }

    /**
     * Get files from directory path, sort them by date modified and return sorted array of full path to files
     *
     * @param string $directoryPath
     * @return array
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function getExportFiles(string $directoryPath): array
    {
        $sortedFiles = [];
        $files = $this->directory->getDriver()->readDirectoryRecursively($directoryPath);
        if (empty($files)) {
            return [];
        }
        foreach ($files as $filePath) {
            $filePath = $this->directory->getAbsolutePath($filePath);
            if ($this->directory->isFile($filePath)) {
                $fileModificationTime = $this->directory->stat($filePath)['mtime'];
                // Check if the current $fileModificationTime already exists as a key in $sortedFiles
                while (isset($sortedFiles[$fileModificationTime])) {
                    $fileModificationTime++; // Increment the $fileModificationTime by 1
                }
                // Add the modified $fileModificationTime as a key in $sortedFiles
                $sortedFiles[$fileModificationTime] = $filePath;
            }
        }
        //sort array elements using key value
        krsort($sortedFiles);

        return $sortedFiles;
    }
}
