<?php

namespace StoreKeeper\StoreKeeper\Model\Export;

use Magento\Framework\Locale\Resolver;
use Magento\Store\Api\StoreConfigManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use StoreKeeper\StoreKeeper\Helper\Base36Coder;
use StoreKeeper\StoreKeeper\Helper\Api\Auth;

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
    const ADD_HEADER_FLAG = 'add_header_';

    private Base36Coder $base36Coder;
    private Auth $authHelper;

    public function __construct(
        Resolver $localeResolver,
        StoreManagerInterface $storeManager,
        StoreConfigManagerInterface $storeConfigManager,
        Base36Coder $base36Coder,
        Auth $authHelper
    ) {
        parent::__construct($localeResolver, $storeManager, $storeConfigManager, $authHelper);
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
                        $result[self::ADD_HEADER_FLAG . $attributeCode]['ignore_row'] = true;
                    }
                }
                $blueprint = $this->buildBlueprintData($configurableAttributes);
                $compoundAlias = $blueprint['alias'];

                if (!isset($result[$compoundAlias])) {
                    $result[$compoundAlias] = array_combine(self::HEADERS_PATHS, $blueprint);
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
    public function buildBlueprintData(array $configurableAttributes): array
    {
        $attributeData = [];
        $skuPattern = "{{sku}}";
        $titlePattern = "{{title}}";

        foreach ($configurableAttributes as $configurableAttribute) {
            $attributeData['name'][] = $configurableAttribute['label'];
            $attributeData['alias'][] = $this->formatAlias($configurableAttribute['attribute_code']);
            $skuPattern .= "-{{content_vars['{$configurableAttribute['attribute_code']}']['value']}}";
            $titlePattern .= "-{{content_vars['{$configurableAttribute['attribute_code']}']['value_label']}}";
        }

        $name = implode(' & ', $attributeData['name']);
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
        $paths = self::HEADERS_PATHS;
        $labels = self::HEADERS_LABELS;
        foreach ($blueprintData as $key => $value) {
            if (str_contains($key, self::ADD_HEADER_FLAG)) {
                $key = str_replace(self::ADD_HEADER_FLAG, '', $key );
                $pathData = $this->getEncodedAttributePathData($key);
                $labelData = $this->getLabelData($value['path://name']);
                array_push($paths, ...$pathData);
                array_push($labels, ...$labelData);
            }
        }

        return [
            'paths' => $paths,
            'labels' => $labels
        ];
    }

    /**
     * @param string $key
     * @return array
     */
    private function getEncodedAttributePathData(string $key): array
    {
        $encodedName = $this->base36Coder->encode($this->formatAlias($key));

        return [
            "path://attribute.encoded__$encodedName.is_configurable",
            "path://attribute.encoded__$encodedName.is_synchronized"
        ];
    }

    /**
     * @param string $label
     * @return array
     */
    private function getLabelData(string $label): array
    {
                return [
                    "$label (Configurable)",
                    "$label (Synchronized)"
                ];
    }

    /**
     * @param array $labels
     * @param array $dataRow
     * @return array
     */
    public function getBlueprintRow(array $labels, array $dataRow): array
    {
        $diff = array_diff_key($labels, $dataRow);
        foreach ($diff as $key => $value) {
            if (mb_substr_count($dataRow['path://sku_pattern'], 'content_vars') > 1) {
                $compoundLabelData = explode('&', $dataRow['path://name']);
                foreach ($compoundLabelData as $compoundLabelItem) {
                    $dataRow[$key] = $this->isLabelItemMatchHeader(trim($compoundLabelItem), $value) ? 'yes' : 'no';
                    if ($dataRow[$key] =='yes') {
                        break;
                    }
                }
            } else{
                $dataRow[$key] = $this->isLabelItemMatchHeader($dataRow['path://name'], $value ) ? 'yes' : 'no';
            }

        }

        return $dataRow;
    }

    /**
     * @param string $labelItem
     * @param string $headerLabel
     * @return bool
     */
    private function isLabelItemMatchHeader(string $labelItem, string $headerLabel): bool
    {
        return str_starts_with($headerLabel, $labelItem) && !str_contains($headerLabel, 'Synchronized');
    }
}
