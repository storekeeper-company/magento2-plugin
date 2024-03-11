<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use Magento\Framework\Locale\Resolver;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Eav\Api\Data\AttributeSetInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Catalog\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use StoreKeeper\StoreKeeper\Helper\Base36Coder;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

class AttributeExportManager extends AbstractExportManager
{
    const HEADERS_PATHS = [
        'path://name',
        'path://label',
        'path://translatable.lang',
        'path://is_main_lang',
        'path://is_options',
        'path://type',
        'path://required',
        'path://published',
        'path://unique'
    ];
    const HEADERS_LABELS = [
        'Name',
        'Label',
        'Language',
        'Is main language',
        'Has options',
        'Options type',
        'Required',
        'Published',
        'Unique'
    ];
    const PRODUCT_ENTITY_TYPE_ID = '4';

    private AttributeSetRepositoryInterface $attributeSetRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private AttributeCollectionFactory $attributeCollectionFactory;
    private AttributeRepositoryInterface $attributeRepository;
    private Base36Coder $base36Coder;
    private AttributeSetCollectionFactory $attributeSetCollectionFactory;
    private Auth $authHelper;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeCollectionFactory $attributeCollectionFactory,
        AttributeRepositoryInterface $attributeRepository,
        Base36Coder $base36Coder,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
    Auth $authHelper
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->attributeRepository = $attributeRepository;
        $this->base36Coder = $base36Coder;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    /**
     * @param array $attributes
     * @return array
     */
    public function getAttributeExportData(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $attribute) {
            $hasOption = $attribute->usesSource();
            $data = [
                $attribute->getAttributeCode(), //'path://name'
                $attribute->getFrontendLabel(), //'path://label'
                $this->getCurrentLocale(), //'path://translatable.lang'
                'yes', //'path://is_main_lang'
                $hasOption ? 'yes' : 'no', //'path://is_options'
                $hasOption ? 'string' : null, //'path://type'
                $attribute->getIsRequired() ? 'yes' : 'no', //'path://required'
                'true', //'path://published'
                $attribute->getIsUnique() ? 'yes' : 'no' //'path://unique'
            ];
            $result[] = array_combine(self::HEADERS_PATHS, $data);
        }

        return $result;
    }

    /**
     * @param array $attributeData
     * @return array
     */
    public function getHeaderCols(array $attributeData): array
    {
                $headers=[];
        $paths = self::HEADERS_PATHS;
        $labels = self::HEADERS_LABELS;
        foreach ($this->getAttributeSetList() as $attributeSet) {
            $attributeSetName = $attributeSet->getAttributeSetName();
                array_push($paths, $this->getEncodedAttributePath($attributeSetName));
                array_push($labels, $attributeSetName);
        }
                $headers=[
            'paths' =>$paths,
            'labels' =>$labels
        ];

        return $headers;
    }

    /**
     * @param string $key
     * @return string
     */
    private function getEncodedAttributePath(string $key): string
    {
        return "path://attribute_set.encoded__{$this->base36Coder->encode($key)}.is_assigned";
    }

    /**
     * @return array|null
     */
    private function getAttributeSetList(): array
    {
        $attributeSetList = [];

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeSet = $this->attributeSetRepository->getList($searchCriteria);

        if ($attributeSet->getTotalCount()) {
            $attributeSetList = $attributeSet->getItems();
        }

        return $attributeSetList;
    }

    public function getArrtibuteSetIds(string $attributeSetName, string $attributeCode)
    {
        $attributeSet = $this->attributeSetCollectionFactory->create()->addFieldToSelect(
            '*'
        )->addFieldToFilter(
            'attribute_set_name',
            $attributeSetName
        )->addFieldToFilter(
            'entity_type_id',
            self::PRODUCT_ENTITY_TYPE_ID
        );
        $attributesCollection=$this->attributeCollectionFactory->create()->setAttributeSetFilter($attributeSet->getFirstItem()->getId())->load();

        return $attributesCollection->getAllIds();
    }

    /**
     * @param string $attributeCode
     * @return int|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttributeId(string $attributeCode): ?int
    {
        return$this->attributeRepository->get(Product::ENTITY, $attributeCode)->getAttributeId();
    }

    /**
     * @param array $labels
     * @param array $dataRow
     * @return array
     */
    public function getAttributeRow(array $labels, array $dataRow): array
    {
        $diff = array_diff_key($labels, $dataRow);
        foreach ($diff as $key => $value) {
            $arrtibuteSetIds = $this->getArrtibuteSetIds($value, $dataRow['path://name']);
            $attributeId = $this->getAttributeId($dataRow['path://name']);
            $dataRow[$key] = in_array($attributeId, $arrtibuteSetIds) ? 'yes' : 'no';
        }

        return $dataRow;
    }
}
