<?php

namespace StoreKeeper\StoreKeeper\Test\Integration;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use StoreKeeper\ApiWrapper\Exception\GeneralException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\Serialize\Serializer\Json;
use StoreKeeper\StoreKeeper\Helper\Api\Products as ApiProducts;

class ImportExistingProduct extends AbstractTestCase
{
    const CONTENT_VARS = [
        0 => [
            'label' => 'attached attribute',
            'attribute_id' => 1,
            'name' => 'attached-attribute',
            'value' => 'Test',
            'attribute_published' => true,
            'attribute_option_order' => 0,
            'attribute_order' => 0
        ],
        1 => [
            'label' => 'color attr',
            'attribute_id' => 2,
            'attribute_option_id' => 237,
            'value_label' => 'color 2',
            'name' => 'color-attr',
            'value' => 'color-2',
            'attribute_published' => true,
            'attribute_option_order' => 0,
            'attribute_order' => 0,
            'attribute_option_color_hex' => '#d93d3d'
        ],
        2 => [
            'label' => 'string options attr',
            'attribute_id' => 3,
            'attribute_option_id' => 238,
            'value_label' => 'string option 1',
            'name' => 'string-options-attr',
            'value' => 'string-option-1',
            'attribute_published' => true,
            'attribute_option_order' => 0,
            'attribute_order' => 0
        ],
    ];

    const PRODUCT_RESPONSE = [
        'data' => [
            0 => [
                'product_price' => [
                    'ppu' => 11.00
                ],
                'product_default_price' => [
                    'ppu' => 11.00
                ],
                'flat_product' => [
                    'title' => 'Simple Product 2',
                    'body' => 'Simple Body Description',
                    'slug' => '',
                    'attribute_set_name' => 'New Attribute set',
                    'attribute_set_alias' => 'new-attribute-set',
                    'product' => [
                        'product_stock' => [
                            'value' => self::UPDATED_STOCK_ITEM_VALUE,
                            'unlimited' => true
                        ],
                        'active' => 1,
                        'sku' => self::SIMPLE_PRODUCT_SKU,
                        'type' => 'simple'
                    ],
                    'content_vars' => self::CONTENT_VARS
                ],
                'product_id' => 7,
                'orderable_stock_value' => self::UPDATED_STOCK_ITEM_VALUE

            ]
        ]
    ];

    const STRING_ATTR = [
        'id' => 1,
        'type' => 'string',
        'is_options' => false
    ];

    const COLOR_ATTR = [
        'id' => 2,
        'type' => 'color',
        'is_options' => true
    ];
    const SELECT_ATTR = [
        'id' => 3,
        'type' => 'string',
        'is_options' => true
    ];


    protected $apiProducts;
    protected $storeManager;
    protected $productFactory;
    protected $attributes;
    protected $productRepository;
    protected $configHelper;
    protected $sourceItemsProcessor;
    protected $swatchHelper;
    protected $attributeApiClientMock;
    protected $eavSetupFactory;
    protected $moduleDataSetup;
    protected $searchCriteriaBuilder;
    protected $attributeSetRepository;
    protected $attributeGroupRepository;
    protected $attributeRepository;
    protected $entityTypeFactory;
    protected $productAction;
    protected $attributeFactory;
    protected $swatchFactory;
    protected $optionCollectionFactory;
    protected $optionFactory;
    protected $attributeOptionLabel;
    protected $attributeOptionManagement;
    protected $attributeSetFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $objectManager = new ObjectManager($this);
        $this->orderApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\OrderApiClient::class);
        $this->attributeApiClientMock = $this->createMock(\StoreKeeper\StoreKeeper\Api\AttributeApiClient::class);

        //Prepare $this->attributes constructor
        $this->eavSetupFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Setup\EavSetupFactory::class);
        $this->moduleDataSetup = Bootstrap::getObjectManager()->create(\Magento\Framework\Setup\ModuleDataSetupInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
        $this->attributeSetRepository = Bootstrap::getObjectManager()->create(\Magento\Eav\Api\AttributeSetRepositoryInterface::class);
        $this->attributeGroupRepository = Bootstrap::getObjectManager()->create(\Magento\Eav\Api\AttributeGroupRepositoryInterface::class);
        $this->attributeRepository = Bootstrap::getObjectManager()->create(\Magento\Eav\Api\AttributeRepositoryInterface::class);
        $this->entityTypeFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Model\Entity\TypeFactory::class);
        $this->productAction = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Product\Action::class);
        $this->attributeFactory = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory::class);
        $this->swatchFactory = Bootstrap::getObjectManager()->create(\Magento\Swatches\Model\SwatchFactory::class);
        $this->optionCollectionFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory::class);
        $this->optionFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Model\Entity\Attribute\OptionFactory::class);
        $this->attributeOptionLabel = Bootstrap::getObjectManager()->create(\Magento\Eav\Api\Data\AttributeOptionLabelInterface::class);
        $this->attributeOptionManagement = Bootstrap::getObjectManager()->create(\Magento\Eav\Api\AttributeOptionManagementInterface::class);

        $this->storeManager = Bootstrap::getObjectManager()->create(\Magento\Store\Model\StoreManagerInterface::class);
        $this->productFactory = Bootstrap::getObjectManager()->create(\Magento\Catalog\Model\ProductFactory::class);
        $this->productRepository = Bootstrap::getObjectManager()->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->configHelper = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Helper\Config::class);
        $this->sourceItemsProcessor = Bootstrap::getObjectManager()->create(\Magento\InventoryCatalogApi\Model\SourceItemsProcessorInterface::class);
        $this->swatchHelper = Bootstrap::getObjectManager()->create(\Magento\Swatches\Helper\Data::class);
        $this->attributeSetFactory = Bootstrap::getObjectManager()->create(\Magento\Eav\Model\Entity\Attribute\SetFactory::class);

        $this->orderApiClientMock->method('getNaturalSearchShopFlatProductForHooks')
            ->willReturn(
                self::PRODUCT_RESPONSE
            );

        $this->attributeApiClientMock->method('getAttributesByIds')
            ->willReturn([
                0 => self::STRING_ATTR,
                1 => self::COLOR_ATTR,
                2 => self::SELECT_ATTR
            ]);

        $this->orderApiClientMock->method('getUpsellShopProductIds')->willReturn([]);
        $this->orderApiClientMock->method('getCrossSellShopProductIds')->willReturn([]);

        $this->attributes = $objectManager->getObject(
            \StoreKeeper\StoreKeeper\Helper\Api\Attributes::class,
            [
                'eavSetupFactory' => $this->eavSetupFactory,
                'moduleDataSetup' => $this->moduleDataSetup,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilder,
                'attributeSetRepository' => $this->attributeSetRepository,
                'attributeGroupRepository' => $this->attributeGroupRepository,
                'attributeRepository' => $this->attributeRepository,
                'productAction' => $this->productAction,
                'attributeFactory' => $this->attributeFactory,
                'swatchFactory' =>  $this->swatchFactory,
                'optionCollectionFactory' => $this->optionCollectionFactory,
                'optionFactory' => $this->optionFactory,
                'attributeOptionLabel' => $this->attributeOptionLabel,
                'attributeOptionManagement' => $this->attributeOptionManagement,
                'attributeApiClient' => $this->attributeApiClientMock,
                'attributeSetFactory' => $this->attributeSetFactory
            ]);

        $this->apiProducts = $objectManager->getObject(
            ApiProducts::class,
            [
                'orderApiClient' => $this->orderApiClientMock,
                'authHelper' => $this->authHelper,
                'productCollectionFactory' => $this->productCollectionFactory,
                'stockRegistry' => $this->stockRegistry,
                'productApiClient' => $this->productApiClientMock,
                'storeManager' => $this->storeManager,
                'productFactory' => $this->productFactory,
                'attributes' => $this->attributes,
                'productRepository' => $this->productRepository,
                'configHelper' => $this->configHelper,
                'sourceItemsProcessor' => $this->sourceItemsProcessor,
                'entityTypeFactory' => $this->entityTypeFactory
            ]
        );
    }

    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_stock_source default
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/product_simple_without_custom_options.php
     *
     * @return void
     */
    public function testUpdateById(): void
    {
        $this->apiProducts->updateById(1, 7);

        //Load product with processed test data
        $product = $this->getProduct(self::SIMPLE_PRODUCT_SKU);

        //Assert 'string' attribute 'attached-attribute'
        $this->assertEquals($this->getStringAttributeExpectedValue(), $product->getAttachedAttribute());

        //Assert 'select' active option from attribute 'string-options-attr'
        $attribute = $product->getResource()->getAttribute('string_options_attr');
        $optionText = $attribute->getSource()->getOptionText($product->getStringOptionsAttr());
        $this->assertEquals($this->getSelectAttributeExpectedValue(), $optionText);

        //Assert 'color' active option from attribute 'color-attr'
        $swatchData = $this->swatchHelper->getSwatchesByOptionsId([$product->getColorAttr()]);
        $swatchColor = $swatchData[$product->getColorAttr()]['value'];
        $this->assertEquals($this->getColorAttributeExpectedValue(), $swatchColor);

        //Assert newly created attribute set by name
        $attributeSet = $this->attributeSetRepository->get($product->getAttributeSetId());
        $this->assertEquals($this->getAttributeSetExpectedName(), $attributeSet->getAttributeSetName());
    }

    private function getStringAttributeExpectedValue(): string
    {
        return self::PRODUCT_RESPONSE['data'][0]['flat_product']['content_vars'][0]['value'];
    }

    private function getColorAttributeExpectedValue(): string
    {
        return self::PRODUCT_RESPONSE['data'][0]['flat_product']['content_vars'][1]['attribute_option_color_hex'];
    }

    private function getSelectAttributeExpectedValue(): string
    {
        return self::PRODUCT_RESPONSE['data'][0]['flat_product']['content_vars'][2]['value_label'];
    }

    private function getAttributeSetExpectedName(): string
    {
        return self::PRODUCT_RESPONSE['data'][0]['flat_product']['attribute_set_name'];
    }
}
