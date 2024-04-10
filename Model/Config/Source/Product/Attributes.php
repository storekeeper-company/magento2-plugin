<?php
namespace StoreKeeper\StoreKeeper\Model\Config\Source\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;

class Attributes implements ArrayInterface
{
    protected $attributeCollectionFactory;

    public function __construct(CollectionFactory $attributeCollectionFactory)
    {
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    public function toOptionArray()
    {
        $options = [];
        $options[] = ['label' => '------ Not mapped ------', 'value' => 'not-mapped'];
        $attributes = $this->attributeCollectionFactory->create();
        foreach ($attributes as $attribute) {
            $options[] = [
                'label' => $attribute->getDefaultFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            ];
        }
        return $options;
    }
}
