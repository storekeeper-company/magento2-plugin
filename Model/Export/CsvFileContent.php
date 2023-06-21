<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Store\Model\Store;
use StoreKeeper\StoreKeeper\Model\Export\ProductExportManager;
use StoreKeeper\StoreKeeper\Model\Export\CustomerExportManager;
use StoreKeeper\StoreKeeper\Model\Export\CategoryExportManager;
use StoreKeeper\StoreKeeper\Model\Export\AttributeExportManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\Adapter\Csv;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Data\Collection\AbstractDb;

class CsvFileContent
{
    const CATALOG_PRODUCT_ENTITY = 'catalog_product';
    const CUSTOMER_ENTITY = 'customer';
    const CATEGORY_ENTITY = 'category';
    const ATTRIBUTE_ENTITY = 'attribute';
    const PAGE_SIZE = 500;

    private ProductExportManager $productExportManager;
    private CustomerExportManager $customerExportManager;
    private CategoryExportManager $categoryExportManager;
    private AttributeExportManager $attributeExportManager;
    private DateTime $dateTime;
    private Csv $writer;
    private ProductCollectionFactory $productCollectionFactory;
    private CustomerCollectionFactory $customerCollectionFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private AttributeFactory $attributeFactory;

    public function __construct(
        ProductExportManager $productExportManager,
        CustomerExportManager $customerExportManager,
        CategoryExportManager $categoryExportManager,
        AttributeExportManager $attributeExportManager,
        DateTime $dateTime,
        Csv $writer,
        ProductCollectionFactory $productCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeFactory $attributeFactory
    ) {
        $this->productExportManager = $productExportManager;
        $this->customerExportManager = $customerExportManager;
        $this->categoryExportManager = $categoryExportManager;
        $this->attributeExportManager = $attributeExportManager;
        $this->dateTime = $dateTime;
        $this->writer = $writer;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attributeFactory = $attributeFactory;
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
            $entityCollection->setCurPage($page)->setPageSize(self::PAGE_SIZE);
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
     * @return AbstractDb
     */
    private function getEntityCollection(string $entityType): AbstractDb
    {
        if ($entityType == self::CATALOG_PRODUCT_ENTITY) {
            $entityCollection = $this->productCollectionFactory->create();
            $entityCollection->addFieldToSelect('*');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $entityCollection->addMediaGalleryData();
            $entityCollection->setOrder('entity_id', 'asc');
        }
        if ($entityType == self::CUSTOMER_ENTITY) {
            $entityCollection = $this->customerCollectionFactory->create();
            $entityCollection->setOrder('entity_id', 'asc');
        }
        if ($entityType == self::CATEGORY_ENTITY) {
            $entityCollection = $this->categoryCollectionFactory->create();
            $entityCollection->addFieldToSelect('*');
            $entityCollection->setOrder('entity_id', 'asc');
        }

        if ($entityType == self::ATTRIBUTE_ENTITY) {
            $entityCollection = $this->attributeFactory->create()->getCollection();
            $entityCollection->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);
            $entityCollection->addFieldToSelect('*');
        }

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
        if ($entityType == self::CATEGORY_ENTITY) {
            $exportData = $this->categoryExportManager->getCategoryExportData($items);
        }
        if ($entityType == self::ATTRIBUTE_ENTITY) {
            $exportData = $this->attributeExportManager->getAttributeExportData($items);
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
            $headerColsLabels = $this->productExportManager->getMappedHeadersLabels(ProductExportManager::HEADERS_PATHS, ProductExportManager::HEADERS_LABELS);
        }
        if ($entityType == self::CUSTOMER_ENTITY) {
            $headerCols = CustomerExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->customerExportManager->getMappedHeadersLabels(CustomerExportManager::HEADERS_PATHS, CustomerExportManager::HEADERS_LABELS);
        }
        if ($entityType == self::CATEGORY_ENTITY) {
            $headerCols = CategoryExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->categoryExportManager->getMappedHeadersLabels(CategoryExportManager::HEADERS_PATHS, CategoryExportManager::HEADERS_LABELS);
        }
        if ($entityType == self::ATTRIBUTE_ENTITY) {
            $headerCols = AttributeExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->attributeExportManager->getMappedHeadersLabels(AttributeExportManager::HEADERS_PATHS, AttributeExportManager::HEADERS_LABELS);
        }
        $headerColsData = [
            'cols' => $headerCols,
            'labels' => $headerColsLabels
        ];

        return $headerColsData;
    }
}
