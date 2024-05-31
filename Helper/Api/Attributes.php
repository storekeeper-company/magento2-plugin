<?php

declare(strict_types=1);

namespace StoreKeeper\StoreKeeper\Helper\Api;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductAction;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Eav\Api\AttributeGroupRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Swatches\Model\SwatchFactory;
use Magento\Eav\Model\Entity\Attribute\OptionFactory;
use Magento\Eav\Api\Data\AttributeOptionLabelInterface;
use Magento\Eav\Api\AttributeOptionManagementInterface;

class Attributes extends AbstractHelper
{
    const STOREKEEPER_GROUP_NAME = 'StoreKeeper';

    private EavSetupFactory $eavSetupFactory;
    private ModuleDataSetupInterface $moduleDataSetup;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private AttributeSetRepositoryInterface $attributeSetRepository;
    private AttributeGroupRepositoryInterface $attributeGroupRepository;
    private AttributeRepositoryInterface $attributeRepository;
    private TypeFactory $entityTypeFactory;
    private Config $eavConfig;
    private ProductAction $productAction;
    private AttributeFactory $attributeFactory;
    private SwatchFactory $swatchFactory;
    private OptionCollectionFactory $optionCollectionFactory;
    private OptionFactory $optionFactory;
    private AttributeOptionLabelInterface $attributeOptionLabel;
    private AttributeOptionManagementInterface $attributeOptionManagement;

    /**
     * Constructor
     *
     * @param EavSetupFactory $eavSetupFactory
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     * @param AttributeGroupRepositoryInterface $attributeGroupRepository
     * @param AttributeRepositoryInterface $attributeRepository
     * @param TypeFactory $entityTypeFactory
     * @param ProductAction $productAction
     * @param AttributeFactory $attributeFactory
     * @param SwatchFactory $swatchFactory
     * @param OptionCollectionFactory $optionCollectionFactory
     * @param OptionFactory $optionFactory
     * @param AttributeOptionLabelInterface $attributeOptionLabel
     * @param AttributeOptionManagementInterface $attributeOptionManagement
     */
    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeSetRepositoryInterface $attributeSetRepository,
        AttributeGroupRepositoryInterface $attributeGroupRepository,
        AttributeRepositoryInterface $attributeRepository,
        TypeFactory $entityTypeFactory,
        ProductAction $productAction,
        AttributeFactory $attributeFactory,
        SwatchFactory $swatchFactory,
        OptionCollectionFactory $optionCollectionFactory,
        OptionFactory $optionFactory,
        AttributeOptionLabelInterface $attributeOptionLabel,
        AttributeOptionManagementInterface $attributeOptionManagement
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeGroupRepository = $attributeGroupRepository;
        $this->attributeRepository = $attributeRepository;
        $this->entityTypeFactory = $entityTypeFactory;
        $this->productAction = $productAction;
        $this->attributeFactory = $attributeFactory;
        $this->swatchFactory = $swatchFactory;
        $this->optionCollectionFactory = $optionCollectionFactory;
        $this->optionFactory = $optionFactory;
        $this->attributeOptionLabel = $attributeOptionLabel;
        $this->attributeOptionManagement = $attributeOptionManagement;
    }

    /**
     * Handle custom attributes from SK, update existing appripriate M2 attribute values for product
     * Create, assign to attribute set and set value for new attributes
     * Throw exception if SK attribute missing attribute name, label, value and/or attribute set
     *
     * @param array $flat_product
     * @param Product $target
     * @param string $storeId
     * @return Product
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processProductAttributes(array $flat_product, Product $target, string $storeId): Product
    {
        $attributesToSave = [];

        foreach ($flat_product['content_vars'] as $attribute) {
            if (array_key_exists('attribute_id', $attribute)
                && array_key_exists('name', $attribute)
                && array_key_exists('label', $attribute)
                && array_key_exists('value', $attribute)
                && array_key_exists('attribute_set_name', $flat_product)
            ) {
                try {
                    /**
                     * Can cause potential conflicts - SK's attributes might by named as letters and or '-' and/or '_' symbols
                     * Magento allows only letters and/or '_'
                     */
                    $attributeCode = str_replace('-', '_', $attribute['name']);
                    $attributeLabel = $attribute['label'];
                    $attributeValue = $attribute['value'];
                    $attributeSetName = $flat_product['attribute_set_name'];

                    $attributeEntity = $this->attributeRepository->get('catalog_product', $attributeCode);
                } catch (NoSuchEntityException $e) {
                    $catalogEntity = $this->entityTypeFactory->create()->loadByCode(Product::ENTITY);
                    $catalogEntityId = $catalogEntity->getId();

                    $attributeEntity = $this->createAttribute(
                        $catalogEntityId,
                        $attributeCode,
                        $attributeLabel,
                        $attribute
                    );
                    if ($this->attributeIsVisualColorSwatch($attribute)) {
                        $this->attachSwatchesToOptions($attribute, $attributeEntity);
                    }
                    $this->attachAttributeSet($catalogEntityId, $attributeCode, $attributeSetName);
                }

                /**
                 * If product is new - set attribute value.
                 * If product exist - populate array with atrribute data, and save all at once later in method
                 */
                 if ($this->attributeIsVisualColorSwatch($attribute) || $this->attributeIsSelect($attribute)) {
                    //In case product attribute is Swatch or Select with options - handle options first
                    //then - add/update existing value
                    if ($attributeEntity->usesSource()) {
                        $option_id = $attributeEntity->getSource()->getOptionId($attribute['value_label']);
                        //If option id exist - compare it to existing product attribute value
                        if ($option_id) {
                            if (!$target->getId()) {
                                $target->setData($attributeCode, $option_id);
                            } else if ($target->getData($attributeCode) != $option_id) {
                                $attributesToSave[$attributeCode] = $option_id;
                            }
                        } else {
                            //Create option for attribute and assign it to product
                            $option = $this->optionFactory->create();
                            $option->setValue($attribute['value']);
                            $this->attributeOptionLabel->setStoreId(0);
                            $this->attributeOptionLabel->setLabel($attribute['value_label']);
                            $option->setLabel($attribute['value_label']);
                            $option->setStoreLabels([$this->attributeOptionLabel]);
                            $sortOrder = (array_key_exists('attribute_option_order', $attribute)) ? $attribute['attribute_option_order'] : 0;
                            $option->setSortOrder($sortOrder);
                            $option->setIsDefault(false);
                            $optionId = $this->attributeOptionManagement->add(
                                Product::ENTITY,
                                $attributeEntity->getAttributeId(),
                                $option
                            );

                            if ($optionId) {
                                if ($this->attributeIsVisualColorSwatch($attribute)) {
                                    $this->attachSwatchesToOptions($attribute, $attributeEntity);
                                }
                                if (!$target->getId()) {
                                    $target->setData($attributeCode, $optionId);
                                } else if ($target->getData($attributeCode) != $optionId) {
                                    $attributesToSave[$attributeCode] = $optionId;
                                }
                            }
                        }
                    }
                } else if ($this->attributeIsText($attribute)) {
                     //In case product attribute is String/Text - add/update existing value
                     if (!$target->getId()) {
                         $target->setData($attributeCode, $attributeValue);
                     } else if ($target->getData($attributeCode) != $attributeValue) {
                         $attributesToSave[$attributeCode] = $attributeValue;
                     }
                 }
            } else {
                throw new \Exception('Missing attribute name, label, value and/or attribute set');
            }
        }

        /**
         * Save modified attributes as one transaction, without triggering whole model save
         */
        if (!empty($attributesToSave)) {
            $this->productAction->updateAttributes(
                [$target->getId()],
                $attributesToSave,
                0
            );
        }

        return $target;
    }

    /**
     * @param string $catalogEntityId
     * @param string $attributeCode
     * @param string $attributeLabel
     * @param array $attributeArray
     * @return Attribute
     * @throws \Exception
     */
    private function createAttribute(
        string $catalogEntityId, string $attributeCode, string $attributeLabel, array $attributeArray
    ): Attribute
    {
        $attributeData = $this->populateAttributeData(
            $catalogEntityId, $attributeCode, $attributeLabel, $attributeArray
        );

        $attribute = $this->attributeFactory->create();
        $attribute->addData($attributeData);
        $attribute->save();

        return $attribute;
    }

    /**
     * Assign M2 attribute to existing attribute set mathcing to SK's attribute set by name
     *
     * @param string $catalogEntityId
     * @param string $attributeCode
     * @param string $attributeSetName
     * @return void
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function attachAttributeSet(string $catalogEntityId, string $attributeCode, string $attributeSetName): void
    {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $this->moduleDataSetup->getConnection()->startSetup();

        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'attribute_set_name',
            $attributeSetName,
            'eq'
        )->addFilter(
            'entity_type_id',
            $catalogEntityId,
            'eq'
        )->create();
        $attributeSets = $this->attributeSetRepository->getList($searchCriteria)->getItems();

        if (empty($attributeSets)) {
            throw new \Exception("Attribute set with name '{$attributeSetName}' not found.");
        }

        $attributeSet = reset($attributeSets);
        $attributeSetId = $attributeSet->getAttributeSetId();

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(
                'attribute_set_id', $attributeSetId, 'eq'
            )->create();

        $attributeGroups = $this->attributeGroupRepository->getList($searchCriteria);
        foreach ($attributeGroups->getItems() as $group) {
            if ($group->getAttributeGroupName() == self::STOREKEEPER_GROUP_NAME) {
                $attributeGroupId = $group->getAttributeGroupId();
                break;
            }
        }

        $eavSetup->addAttributeToSet(
            Product::ENTITY,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            null
        );

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @param string $catalogEntityId
     * @param array $attributeArray
     * @return array
     */
    private function populateAttributeData(
        string $catalogEntityId, string $attributeCode,  string $attributeLabel, array $attributeArray
    ): array
    {
        $attributeData = [
            'entity_type_id' => $catalogEntityId,
            'attribute_code' => $attributeCode,
            'frontend_label' => [$attributeLabel],
            'default_frontend_label' => $attributeLabel,
            'is_required' => 0,
            'is_user_defined' => 1,
            'is_unique' => 0,
            'note' => '',
            'frontend_class' => '',
            'is_visible' => 1,
            'is_searchable' => 0,
            'is_filterable' => 0,
            'is_comparable' => 0,
            'is_html_allowed_on_front' => 1,
            'is_used_for_price_rules' => 0,
            'is_filterable_in_search' => 0,
            'used_in_product_listing' => 1,
            'used_for_sort_by' => 0,
            'is_configurable' => 0,
            'is_visible_in_advanced_search' => 0,
            'position' => 0,
            'apply_to' => '',
            'is_wysiwyg_enabled' => 0,
            'is_used_for_promo_rules' => 0,
            'is_used_in_grid' => 0,
            'is_visible_in_grid' => 0,
            'is_filterable_in_grid' => 0,
            'search_weight' => 1,
            'is_used_for_sort_by' => 0,
            'is_global' => ScopedAttributeInterface::SCOPE_GLOBAL,
            'is_visible_on_front' => 0
        ];

        if ($this->attributeIsVisualColorSwatch($attributeArray)) {
            $attributeData['backend_type'] = 'int';
            $attributeData['frontend_input'] = 'select';
            $attributeData['swatch_input_type'] = Swatch::SWATCH_INPUT_TYPE_VISUAL;
            $attributeData['update_product_preview_image'] = 1;
            $attributeData['use_product_image_for_swatch'] = 0;
            $attributeData['option'] = ['value' => [$attributeArray['value'] => [$attributeArray['value_label']]]];
        } elseif ($this->attributeIsSelect($attributeArray)) {
            $attributeData['backend_type'] = 'int';
            $attributeData['frontend_input'] = 'select';
            $attributeData['option'] = ['value' => [$attributeArray['value'] => [$attributeArray['value_label']]]];
        } else {
            $attributeData['backend_type'] = 'varchar';
            $attributeData['frontend_input'] = 'text';
        }

        return $attributeData;
    }

    /**
     * Check if SK attribute is Text ('String' type for SK naming convention)
     * @todo rework after SK product attribute type will be optainable via SK api
     *
     * @param array $attributeArray
     * @return bool
     */
    private function attributeIsText(array $attributeArray): bool
    {
        if (
            array_key_exists('value', $attributeArray)
            && !array_key_exists('value_label', $attributeArray)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if SK attribute is Text ('Color' type with image options for SK naming convention)
     * @todo rework after SK product attribute type will be optainable via SK api
     *
     * @param array $attributeArray
     * @return bool
     */
    private function attributeIsVisualColorSwatch(array $attributeArray): bool
    {
        if (
            array_key_exists('value', $attributeArray)
            && array_key_exists('value_label', $attributeArray)
            && array_key_exists('attribute_option_id', $attributeArray)
            && array_key_exists('attribute_option_color_hex', $attributeArray)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if SK attribute is Text ('String' type with image options for SK naming convention)
     * @todo rework after SK product attribute type will be optainable via SK api
     *
     * @param array $attributeArray
     * @return bool
     */
    private function attributeIsVisualImageSwatch(array $attributeArray): bool
    {
        if (
            array_key_exists('value', $attributeArray)
            && array_key_exists('value_label', $attributeArray)
            && array_key_exists('attribute_option_id', $attributeArray)
            && array_key_exists('attribute_option_image_url', $attributeArray)
            && !array_key_exists('attribute_option_color_hex', $attributeArray)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if SK attribute is Text ('String' type without image options for SK naming convention)
     * @todo rework after SK product attribute type will be optainable via SK api
     *
     * @param array $attributeArray
     * @return bool
     */
    private function attributeIsSelect(array $attributeArray): bool
    {
        if (
            array_key_exists('value', $attributeArray)
            && array_key_exists('value_label', $attributeArray)
            && array_key_exists('attribute_option_id', $attributeArray)
            && !array_key_exists('attribute_option_image_url', $attributeArray)
            && !array_key_exists('attribute_option_color_hex', $attributeArray)
        ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param array $attributeArray
     * @param Attribute $attribute
     * @return void
     * @throws \Exception
     */
    private function attachSwatchesToOptions(array $attributeArray, Attribute $attribute): void
    {
        $optionLabel = $attributeArray['value_label'];
        $optionValue = $attributeArray['attribute_option_color_hex'];

        $optionCollection = $this->optionCollectionFactory->create()->setAttributeFilter($attribute->getAttributeId());

        foreach ($optionCollection as $option) {
            $optionText = $attribute->getSource()->getOptionText($option->getId());
            if ($optionText == $optionLabel) {
                $swatch = $this->swatchFactory->create();
                $swatch->setData([
                    'option_id' => $option->getId(),
                    'store_id' => 0,
                    'type' => Swatch::SWATCH_INPUT_TYPE_VISUAL,
                    'value' => $optionValue
                ]);
                $swatch->save();
            }
        }
    }
}
