<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Store\Model\Store;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ImportExport\Model\Export\Adapter\CsvFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as AttributeOptionCollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class CsvFileContent
{
    const CATALOG_PRODUCT_ENTITY = 'catalog_product';
    const CUSTOMER_ENTITY = 'customer';
    const CATEGORY_ENTITY = 'category';
    const ATTRIBUTE_SET_ENTITY = 'attribute_set';
    const ATTRIBUTE_ENTITY = 'attribute';
    const ATTRIBUTE_OPTION_ENTITY = 'attribute_option';
    const BLUEPRINT_ENTITY = 'blueprint';
    const FULL_EXPORT = 'full_export';
    const PAGE_SIZE = 500;

    private ProductExportManager $productExportManager;
    private CustomerExportManager $customerExportManager;
    private CategoryExportManager $categoryExportManager;
    private AttributeSetExportManager $attributeSetExportManager;
    private AttributeExportManager $attributeExportManager;
    private AttributeOptionExportManager $attributeOptionExportManager;
    private BlueprintExportManager $blueprintExportManager;
    private DateTime $dateTime;
    private CsvFactory $csvFactory;
    private ProductCollectionFactory $productCollectionFactory;
    private CustomerCollectionFactory $customerCollectionFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private AttributeFactory $attributeFactory;
    private AttributeOptionCollectionFactory $attributeOptionCollectionFactory;
    private AttributeSetCollectionFactory $attributeSetCollectionFactory;

    /**
     * Constructor
     *
     * @param ProductExportManager $productExportManager
     * @param CustomerExportManager $customerExportManager
     * @param CategoryExportManager $categoryExportManager
     * @param AttributeSetExportManager $attributeSetExportManager
     * @param AttributeExportManager $attributeExportManager
     * @param AttributeOptionExportManager $attributeOptionExportManager
     * @param BlueprintExportManager $blueprintExportManager
     * @param DateTime $dateTime
     * @param CsvFactory $csvFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param AttributeFactory $attributeFactory
     * @param AttributeOptionCollectionFactory $attributeOptionCollectionFactory
     * @param AttributeSetCollectionFactory $attributeSetCollectionFactory
     */
    public function __construct(
        ProductExportManager $productExportManager,
        CustomerExportManager $customerExportManager,
        CategoryExportManager $categoryExportManager,
        AttributeSetExportManager $attributeSetExportManager,
        AttributeExportManager $attributeExportManager,
        AttributeOptionExportManager $attributeOptionExportManager,
        BlueprintExportManager $blueprintExportManager,
        DateTime $dateTime,
        CsvFactory $csvFactory,
        ProductCollectionFactory $productCollectionFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeFactory $attributeFactory,
        AttributeOptionCollectionFactory $attributeOptionCollectionFactory,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
    ) {
        $this->productExportManager = $productExportManager;
        $this->customerExportManager = $customerExportManager;
        $this->categoryExportManager = $categoryExportManager;
        $this->attributeSetExportManager = $attributeSetExportManager;
        $this->attributeExportManager = $attributeExportManager;
        $this->attributeOptionExportManager = $attributeOptionExportManager;
        $this->blueprintExportManager = $blueprintExportManager;
        $this->dateTime = $dateTime;
        $this->csvFactory = $csvFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeOptionCollectionFactory = $attributeOptionCollectionFactory;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
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
        $writer = $this->csvFactory->create();
        $entityCollection = $this->getEntityCollection($entityType);
        set_time_limit(0);
        $page = 0;
        while (true) {
            ++$page;
            $entityCollection->setPageSize(self::PAGE_SIZE)->setCurPage($page);
            $entityCollection->clear();
            $entityCollection->load();
            if ($entityCollection->count() == 0) {
                break;
            }
            $items = $entityCollection->getItems();
            $exportData = $this->getExportData($entityType, $items);
            if ($page == 1) {
                $headerColsData = $this->getHeaderColsData($entityType, $exportData);
                $writer->setHeaderCols($headerColsData['cols']);
                $writer->writeRow($headerColsData['labels']);
            }
            foreach ($exportData as $dataRow) {
                if ($entityType == self::BLUEPRINT_ENTITY) {
                    $dataRow = $this->blueprintExportManager->getBlueprintRow($headerColsData['cols'], $dataRow);
                }
                if ($entityType == self::ATTRIBUTE_ENTITY) {
                    $dataRow = $this->attributeExportManager->getAttributeRow($headerColsData['labels'], $dataRow);
                }
                if (!array_key_exists('ignore_row', $dataRow)) {
                    $writer->writeRow($dataRow);
                }
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return $writer->getContents();
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
            $entityCollection->setOrder('type_id', 'asc');
        }
        if ($entityType == self::BLUEPRINT_ENTITY) {
            $entityCollection = $this->productCollectionFactory->create();
            $entityCollection->addFieldToFilter(ProductInterface::TYPE_ID, Configurable::TYPE_CODE);
            $entityCollection->addFieldToSelect('*');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
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
        if ($entityType == self::ATTRIBUTE_SET_ENTITY) {
            $entityCollection = $this->attributeSetCollectionFactory->create();
            $entityCollection->setEntityTypeFilter(4);
        }
        if ($entityType == self::ATTRIBUTE_ENTITY) {
            $entityCollection = $this->attributeFactory->create()->getCollection();
            $entityCollection->addFieldToFilter(\Magento\Eav\Model\Entity\Attribute\Set::KEY_ENTITY_TYPE_ID, 4);
            $entityCollection->addFieldToFilter('frontend_label', ['notnull' => true]);
            $entityCollection->addFieldToSelect('*');
        }
        if ($entityType == self::ATTRIBUTE_OPTION_ENTITY) {
            $entityCollection = $this->attributeOptionCollectionFactory->create();
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
        if ($entityType == self::ATTRIBUTE_SET_ENTITY) {
            $exportData = $this->attributeSetExportManager->getAttributeSetExportData($items);
        }
        if ($entityType == self::ATTRIBUTE_ENTITY) {
            $exportData = $this->attributeExportManager->getAttributeExportData($items);
        }
        if ($entityType == self::ATTRIBUTE_OPTION_ENTITY) {
            $exportData = $this->attributeOptionExportManager->getAttributeOptionExportData($items);
        }
        if ($entityType == self::BLUEPRINT_ENTITY) {
            $exportData = $this->blueprintExportManager->getBlueprintExportData($items);
        }

        return $exportData;
    }

    /**
     * @param string $entityType
     * @param array $exportData
     * @return array
     */
    private function getHeaderColsData(string $entityType, array $exportData): array
    {
        if ($entityType == self::CATALOG_PRODUCT_ENTITY) {
            $headerCols = $this->productExportManager->getHeaderPathsExtended();
            $headerColsLabels = $this->productExportManager->getMappedHeadersLabels(
                $this->productExportManager->getHeaderPathsExtended(),
                $this->productExportManager->getHeaderLabelsExtended()
            );
        }
        if ($entityType == self::CUSTOMER_ENTITY) {
            $headerCols = CustomerExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->customerExportManager->getMappedHeadersLabels(
                CustomerExportManager::HEADERS_PATHS,
                CustomerExportManager::HEADERS_LABELS
            );
        }
        if ($entityType == self::CATEGORY_ENTITY) {
            $headerCols = CategoryExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->categoryExportManager->getMappedHeadersLabels(
                CategoryExportManager::HEADERS_PATHS,
                CategoryExportManager::HEADERS_LABELS
            );
        }
        if ($entityType == self::ATTRIBUTE_SET_ENTITY) {
            $headerCols = AttributeSetExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->attributeSetExportManager->getMappedHeadersLabels(
                AttributeSetExportManager::HEADERS_PATHS,
                AttributeSetExportManager::HEADERS_LABELS
            );
        }
        if ($entityType == self::ATTRIBUTE_ENTITY) {
            $headerData = $this->attributeExportManager->getHeaderCols($exportData);
            $headerCols = $headerData['paths'];
            $headerColsLabels = $this->attributeExportManager->getMappedHeadersLabels(
                $headerData['paths'],
                $headerData['labels']
            );
        }
        if ($entityType == self::ATTRIBUTE_OPTION_ENTITY) {
            $headerCols = AttributeOptionExportManager::HEADERS_PATHS;
            $headerColsLabels = $this->attributeOptionExportManager->getMappedHeadersLabels(
                AttributeOptionExportManager::HEADERS_PATHS,
                AttributeOptionExportManager::HEADERS_LABELS
            );
        }
        if ($entityType == self::BLUEPRINT_ENTITY) {
            $headerData = $this->blueprintExportManager->getHeaderCols($exportData);
            $headerCols = $headerData['paths'];
            $headerColsLabels = $this->blueprintExportManager->getMappedHeadersLabels(
                $headerData['paths'],
                $headerData['labels']
            );
        }
        $headerColsData = [
            'cols' => $headerCols,
            'labels' => $headerColsLabels
        ];

        return $headerColsData;
    }

    /**
     * @return array
     */
    public function getAllExportEntityTypes(): array
    {
        return [
            self::CATALOG_PRODUCT_ENTITY,
            self::CUSTOMER_ENTITY,
            self::CATEGORY_ENTITY,
            self::ATTRIBUTE_SET_ENTITY,
            self::ATTRIBUTE_ENTITY,
            self::ATTRIBUTE_OPTION_ENTITY,
            self::BLUEPRINT_ENTITY
        ];
    }
}
