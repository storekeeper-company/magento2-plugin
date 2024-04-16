<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use StoreKeeper\StoreKeeper\Helper\Base36Coder;
use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;

class BlueprintExportDataTest extends AbstractTest
{
    protected $blueprintExportManager;
    protected  $csvFileContent;
    protected  $json;
    protected $base36Coder;

    protected function setUp(): void
    {
        $this->blueprintExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\BlueprintExportManager::class);
        $this->csvFileContent = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\CsvFileContent::class);
        $this->json = Bootstrap::getObjectManager()->create(\Magento\Framework\Serialize\Serializer\Json::class);
        $this->base36Coder = Bootstrap::getObjectManager()->create(Base36Coder::class);
    }
    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_products_with_two_attributes.php
     */
    public function testGetBlueprintExportData()
    {
        $this->assertEquals($this->getTestBlueprintExportData(), $this->csvFileContent->getFileContents('blueprint'));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getBlueprintCsvContent(): array
    {
        $csvData = $this->csvFileContent->getFileContents('blueprint');
        $arrayData = str_getcsv($csvData, "\n");

        return $arrayData;
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTestBlueprintExportData(): string
    {
        $csvData = '';
        $handle = fopen('php://temp/maxmemory:'. (5*1024*1024), 'r+');
        $blueprintArray =  [
            0 => [
                'path://name',
                'path://alias',
                'path://sku_pattern',
                'path://title_pattern',
                'path://attribute.encoded__' . $this->base36Coder->encode('sk_color') . '.is_configurable',
                'path://attribute.encoded__' . $this->base36Coder->encode('sk_color') . '.is_synchronized',
                'path://attribute.encoded__' . $this->base36Coder->encode('sk_size') . '.is_configurable',
                'path://attribute.encoded__' . $this->base36Coder->encode('sk_size') . '.is_synchronized',
                'path://attribute.encoded__' . $this->base36Coder->encode('sk_shoe_size') . '.is_configurable',
                'path://attribute.encoded__' . $this->base36Coder->encode('sk_shoe_size') . '.is_synchronized'
            ],
            1 => [
                'Name',
                'Alias',
                'Sku pattern',
                'Title pattern',
                'Storekeeper Color (Configurable)',
                'Storekeeper Color (Synchronized)',
                'Storekeeper Size (Configurable)',
                'Storekeeper Size (Synchronized)',
                'Storekeeper Shoe Size (Configurable)',
                'Storekeeper Shoe Size (Synchronized)'
            ],
            2 => [
                'Storekeeper Color & Storekeeper Size',
                'sk_color-sk_size',
                "{{sku}}-{{content_vars['sk_color']['value']}}-{{content_vars['sk_size']['value']}}",
                "{{title}}-{{content_vars['sk_color']['value_label']}}-{{content_vars['sk_size']['value_label']}}",
                'yes',
                'no',
                'yes',
                'no',
                'no',
                'no'
            ],
            3 => [
                'Storekeeper Shoe Size',
                'sk_shoe_size',
                "{{sku}}-{{content_vars['sk_shoe_size']['value']}}",
                "{{title}}-{{content_vars['sk_shoe_size']['value_label']}}",
                'no',
                'no',
                'no',
                'no',
                'yes',
                'no'
            ]
        ];

        foreach ($blueprintArray as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);

        while (!feof($handle)) {
            $csvData .= fread($handle, 8192);
        }

        fclose($handle);

        return $csvData;
    }
}
