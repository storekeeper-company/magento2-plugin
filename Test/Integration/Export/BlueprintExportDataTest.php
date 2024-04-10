<?php

namespace StoreKeeper\StoreKeeper\Test\Integration\Export;

use StoreKeeper\StoreKeeper\Test\Integration\AbstractTest;
use Magento\TestFramework\Helper\Bootstrap;

class BlueprintExportDataTest extends AbstractTest
{
    protected $blueprintExportManager;
    protected  $csvFileContent;
    protected  $json;

    protected function setUp(): void
    {
        $this->blueprintExportManager = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\BlueprintExportManager::class);
        $this->csvFileContent = Bootstrap::getObjectManager()->create(\StoreKeeper\StoreKeeper\Model\Export\CsvFileContent::class);
        $this->json = Bootstrap::getObjectManager()->create(\Magento\Framework\Serialize\Serializer\Json::class);
    }
    /**
     * @magentoConfigFixture current_store storekeeper_general/general/storekeeper_shop_language nl
     * @magentoConfigFixture current_store general/locale/code nl_NL
     * @magentoDataFixture StoreKeeper_StoreKeeper::Test/Integration/_files/configurable_products_with_two_attributes.php
     */
    public function testGetBlueprintExportData()
    {
        $this->assertEquals($this->getTestBlueprintExportData(), $this->getBlueprintCsvContent());
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getBlueprintCsvContent(): array
    {
        $data = $this->csvFileContent->getFileContents('blueprint');
        $data = explode(',', $this->json->unserialize(str_replace('\n', ',', $this->json->serialize($data))));
        $data = array_filter($data, function ($value) {
            return !empty($value);
        });

        return $data;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTestBlueprintExportData(): array
    {
        return [
            0 => 'path://name',
            1 => 'path://alias',
            2 => 'path://sku_pattern',
            3 => 'path://title_pattern',
            4 => 'path://attribute.encoded__3rt6eutvzsqn6el82yobt8wuw3by8ahxzddg.is_configurable',
            5 => 'path://attribute.encoded__3rt6eutvzsqn6el82yobt8wuw3by8ahxzddg.is_synchronized',
            6 => 'path://attribute.encoded__qtrhln8jikdgtjqxh2kzzzdno7ozk0u07xxes.is_configurable',
            7 => 'path://attribute.encoded__qtrhln8jikdgtjqxh2kzzzdno7ozk0u07xxes.is_synchronized',
            8 => 'Name',
            9 => 'Alias',
            10 => '"Sku pattern"',
            11 => '"Title pattern"',
            12 => '"Test Configurable First (Configurable)"',
            13 => '"Test Configurable First (Synchronized)"',
            14 => '"Test Configurable Second (Configurable)"',
            15 => '"Test Configurable Second (Synchronized)"',
            16 => '"Test Configurable First"',
            17 => 'test_configurable_first',
            18 => '{{sku}}-{{content_vars[\'test_configurable_first\'][\'value\']}}',
            19 => '{{title}}-{{content_vars[\'test_configurable_first\'][\'value_label\']}}',
            20 => 'yes',
            21 => 'no',
            22 => 'no',
            23 => 'no',
            24 => '"Test Configurable Second"',
            25 => 'test_configurable_second',
            26 => '{{sku}}-{{content_vars[\'test_configurable_second\'][\'value\']}}',
            27 => '{{title}}-{{content_vars[\'test_configurable_second\'][\'value_label\']}}',
            28 => 'no',
            29 => 'no',
            30 => 'yes',
            31 => 'no',
            32 => '"Test Configurable First Test Configurable Second"',
            33 => 'test_configurable_first-test_configurable_second',
            34 => '{{sku}}-{{content_vars[\'test_configurable_first\'][\'value\']}}-{{content_vars[\'test_configurable_second\'][\'value\']}}',
            35 => '{{title}}-{{content_vars[\'test_configurable_first\'][\'value_label\']}}-{{content_vars[\'test_configurable_second\'][\'value_label\']}}',
            36 => 'yes',
            37 => 'no',
            38 => 'yes',
            39 => 'no',
        ];
    }
}
