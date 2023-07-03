<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;
use StoreKeeper\StoreKeeper\Helper\Base36Coder;

class BlueprintExportManager extends AbstractExportManager
{
    private Base36Coder $base36Coder;

    const HEADERS_PATHS = [
        'path://name',
        'path://alias',
        'path://sku_pattern',
        'path://title_pattern'
    ];
    const HEADERS_LABELS = [
        'Name',
        'Alias',
        'Sku pattern',
        'Title pattern'
    ];
    const ADD_HEADER_FLAG = 'add_header_';

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        Base36Coder $base36Coder
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager);
        $this->base36Coder = $base36Coder;
    }

    /**
     * @param array $configurableProducts
     * @return array
     */
    public function getBlueprintExportData(array $configurableProducts): array
    {
        $result = [];

        foreach ($configurableProducts as $product) {
            $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributesAsArray($product);

            if (count($configurableAttributes) > 1) {
                foreach ($configurableAttributes as $configurableAttribute) {
                    $attributeCode = $configurableAttribute['attribute_code'];
                    if (!isset($result[$attributeCode])) {
                        $result[self::ADD_HEADER_FLAG . $attributeCode] = array_combine(self::HEADERS_PATHS, $this->buildBlueprintData([$configurableAttribute]));
                    }
                }
                $blueprint = $this->buildBlueprintData($configurableAttributes);
                $compoundAlias = $blueprint['alias'];

                if (!isset($result[$compoundAlias])) {
                    $result[$compoundAlias] = array_combine(self::HEADERS_PATHS, $blueprint);;
                }
            } else {
                $configurableAttribute = current($configurableAttributes);
                $attributeCode = $configurableAttribute['attribute_code'];

                if (!isset($result[$attributeCode])) {
                    $result[self::ADD_HEADER_FLAG . $attributeCode] = array_combine(self::HEADERS_PATHS, $this->buildBlueprintData([$configurableAttribute]));
                }
            }
        }

        return $result;
    }

    /**
     * @param array $configurableAttributes
     * @return array
     */
    private function buildBlueprintData(array $configurableAttributes): array
    {
        $attributeData = [];
        $skuPattern = "{{sku}}";
        $titlePattern = "{{title}}";

        foreach ($configurableAttributes as $configurableAttribute) {
            $attributeData['name'][] = $configurableAttribute['label'];
            $attributeData['alias'][] = $configurableAttribute['attribute_code'];
            $skuPattern .= "-{{content_vars['{$configurableAttribute['attribute_code']}']['value']}}";
            $titlePattern .= "-{{content_vars['{$configurableAttribute['attribute_code']}']['value_label']}}";
        }

        $name = implode(' ', $attributeData['name']);
        $alias = implode('-', $attributeData['alias']);

        return [
            'name' => $name,
            'alias' => $alias,
            'sku_pattern' => $skuPattern,
            'title_pattern' => $titlePattern
        ];
    }

    /**
     * @param array $blueprintData
     * @return array
     */
    public function getHeaderCols(array $blueprintData): array
    {
        $headers=[];
        $paths = self::HEADERS_PATHS;
        $labels = self::HEADERS_LABELS;
        foreach ($blueprintData as $key =>$value) {
            if (str_contains($key, self::ADD_HEADER_FLAG)) {
                $key = str_replace(self::ADD_HEADER_FLAG, '', $key );
                $pathData = $this->getEncodedAttributePathData($key);
                $labelData = $this->getLabelData($value['path://name']);
                array_push($paths, ...$pathData);
                array_push($labels, ...$labelData);
            }
        }
        $headers=[
            'paths' =>$paths,
            'labels' =>$labels
        ];

        return $headers;
    }

    /**
     * @param string $key
     * @return array
     */
    private function getEncodedAttributePathData(string $key): array
    {
        $encodedName =$this->base36Coder->encode($key);

        return [
            "attribute.encoded__$encodedName.is_configurable",
            "attribute.encoded__$encodedName.is_synchronized"
        ];
    }

    /**
     * @param string $label
     * @return array
     */
    private function getLabelData(string $label): array
    {
                return[
                    "$label (Configurable)",
                    "$label (Synchronized)"
                ];
    }
}
