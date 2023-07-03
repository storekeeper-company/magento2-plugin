<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use StoreKeeper\StoreKeeper\Model\Export\AbstractExportManager;

class BlueprintExportManager extends AbstractExportManager
{
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
                $blueprint = $this->buildBlueprintData($configurableAttributes);
                $compoundAlias = $blueprint['alias'];

                if (!isset($result[$compoundAlias])) {
                    $result[$compoundAlias] = array_combine(self::HEADERS_PATHS, $blueprint);;
                }
            } else {
                $configurableAttribute = current($configurableAttributes);
                $attributeCode = $configurableAttribute['attribute_code'];

                if (!isset($result[$attributeCode])) {
                    $result[$attributeCode] = array_combine(self::HEADERS_PATHS, $this->buildBlueprintData([$configurableAttribute]));
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
}
