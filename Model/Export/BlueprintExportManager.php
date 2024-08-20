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

            $kind = $this->buildBlueprintData($configurableAttributes);
            if (count($configurableAttributes) === 1) {
                $result[self::ADD_HEADER_FLAG . $kind['path://alias']] = $kind;
            } else {
                foreach ($configurableAttributes as $configurableAttribute) {
                    $attributeCode = $this->formatAlias($configurableAttribute['attribute_code']);
                    if (!isset($result[$attributeCode])) {
                        $result[self::ADD_HEADER_FLAG . $attributeCode] = $this->buildBlueprintData([$configurableAttribute]);
                    }
                }
                $result[ $kind['path://alias']] = $kind;
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
        $extra_result = [];
        $skuPattern = "{{sku}}";
        $titlePattern = "{{title}}";

        foreach ($configurableAttributes as $configurableAttribute) {
            $attributeData['name'][] = $configurableAttribute['label'];
            $attributeData['alias'][] = $alias = $this->formatAlias($configurableAttribute['attribute_code']);
            $skuPattern .= "-{{content_vars['{$alias}']['value']}}";
            $titlePattern .= "-{{content_vars['{$alias}']['value_label']}}";

            $encodedName = $this->base36Coder->encode($alias);
            $extra_result["path://attribute.encoded__$encodedName.is_configurable"] = 'yes';
            $extra_result["path://attribute.encoded__$encodedName.is_synchronized"] = 'no';
        }

        $name = implode(' & ', $attributeData['name']);
        $alias = implode('-', $attributeData['alias']);

        $result = [
            'path://name' => $name,
            'path://alias' => $alias,
            'path://sku_pattern' => $skuPattern,
            'path://title_pattern' => $titlePattern
        ];

        return $result + $extra_result;
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
     * @param array $paths
     * @param array $dataRow
     * @return array
     */
    public function getBlueprintRow(array $paths, array $dataRow): array
    {
        $row  = [];
        foreach ($paths as $path) {
            $row[$path] = $dataRow[$path] ?? 'no';
        }

        return $row;
    }
}
