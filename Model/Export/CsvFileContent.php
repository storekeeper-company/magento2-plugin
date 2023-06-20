<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Store\Model\Store;
use StoreKeeper\StoreKeeper\Model\Export\ProductExportManager;
use StoreKeeper\StoreKeeper\Model\Export\CustomerExportManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\Adapter\Csv;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class CsvFileContent
{
    const CATALOG_PRODUCT_ENTITY = 'catalog_product';
    const CUSTOMER_ENTITY = 'customer';
    const PAGE_SIZE = 500;

    private ProductExportManager $productExportManager;
    private CustomerExportManager $customerExportManager;
    private DateTime $dateTime;
    private Csv $writer;
    private ProductCollectionFactory $productCollectionFactory;
    private CustomerCollectionFactory $customerCollectionFactory;

    public function __construct(
        ProductExportManager $productExportManager,
        CustomerExportManager $customerExportManager,
        DateTime $dateTime,
        Csv $writer,
        ProductCollectionFactory $productCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory
    ) {
        $this->productExportManager = $productExportManager;
        $this->customerExportManager = $customerExportManager;
        $this->dateTime = $dateTime;
        $this->writer = $writer;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
    }

    /**
     * @param string $exportEntity
     * @return string
     */
    public function getFileName(string $exportEntity): string
    {
        $timestamp = $this->dateTime->gmtTimestamp();
        $dateTimeFormatted = $this->dateTime->date('Ymd_His', $timestamp);
        $fileName = 'storekeeper_' . $exportEntity . '_' . $dateTimeFormatted . '.csv';

        return $fileName;
    }

    /**
     * @param string $entity
     * @return string
     * @throws \Exception
     */
    public function getFileContents(string $entityType): string
    {
        $entityCollection = $this->getEntityCollection($entityType);
        set_time_limit(0);
        $page = 0;
        while (true) {
            ++$page;
            $entityCollection->setPage($page, self::PAGE_SIZE);
            if ($entityCollection->count() == 0) {
                break;
            }
            $items = $entityCollection->getItems();
            $exportData = $this->getExportData($entityType, $items);
            if ($page == 1) {
                $headerColsData = $this->getHeaderColsData($entityType);
                $this->writer->setHeaderCols($headerColsData['cols']);
                $this->writer->writeRow($headerColsData['labels']);
            }
            foreach ($exportData as $dataRow) {
                $this->writer->writeRow($dataRow);
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return $this->writer->getContents();
    }

    /**
     * @param string $entityType
     * @return AbstractCollection
     */
    private function getEntityCollection(string $entityType): AbstractCollection
    {
        if ($entityType == self::CATALOG_PRODUCT_ENTITY) {
            $entityCollection = $this->productCollectionFactory->create();
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $entityCollection->addMediaGalleryData();
        }
        if ($entityType == self::CUSTOMER_ENTITY) {
            $entityCollection = $this->customerCollectionFactory->create();
        }
        $entityCollection->setOrder('entity_id', 'asc');

        return $entityCollection;
    }

    /**
     * @param string $entityType
     * @param array $items
     * @return array
     */
    private function getExportData(string $entityType, array $items): array
    {
        if ($entityType == self::CATALOG_PRODUCT_ENTITY) {
            $exportData = $this->productExportManager->getProductExportData($items);
        }
        if ($entityType == self::CUSTOMER_ENTITY) {
            $exportData = $this->customerExportManager->getCustomerExportData($items);
        }

        return $exportData;
    }

    /**
     * @param string $entityType
     * @return array
     */
    private function getHeaderColsData(string $entityType): array
    {
        if ($entityType == self::CATALOG_PRODUCT_ENTITY) {
            $headerCols = ProductExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->productExportManager->getMappedHeadersLabels();
        }
        if ($entityType == self::CUSTOMER_ENTITY) {
            $headerCols = CustomerExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->customerExportManager->getMappedHeadersLabels();
        }
        $headerColsData = [
            'cols' => $headerCols,
            'labels' => $headerColsLabels
        ];

        return $headerColsData;
    }
}
