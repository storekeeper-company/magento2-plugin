<?php

namespace StoreKeeper\StoreKeeper\Test\Unit;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use StoreKeeper\StoreKeeper\Helper\Api\Products;

class SimpleProductStockTest extends TestCase
{
    protected \Magento\TestFramework\ObjectManager $_objectManager;
    /**
     * Bootstrap application before any test
     */
    protected function setUp(): void
    {
        $this->_objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @dataProvider dataProviderTestGetStockProperties
     * @param $result
     * @param $expected
     * @return void
     */
    public function testStock($result, $expected): void
    {
        $products = $this->_objectManager->create(Products::class);
        //Calculating stock data
        $sourceItemData = $products->updateSourceItemStock($sourceItemData, $result, $result);

        //Formatting output array data
        $output = [
            'in_stock' => $products->getInStock($result),
            'manage_stock' => (bool)$sourceItemData['manage_stock'],
            'quantity' => $sourceItemData['quantity'],

        ];
        if (array_key_exists('backorders', $sourceItemData)) {
            $output['backorders'] = ($sourceItemData['backorders'] === true) ? 'yes' : 'no';
        }

        //TODO Assert output array data and expected data
        $this->assertEquals($output, $expected);
    }

    public function dataProviderTestGetStockProperties(): array
    {
        $tests = [];

        $tests['simple product in stock, limited, has 0 orderable stock'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => false,
                    'value' => 0,
                ],
                'orderable_stock_value' => 0,
            ],
            [
                'in_stock' => false,
                'manage_stock' => true,
                'quantity' => 0,
            ],
        ];

        $tests['simple product in stock, unlimited, has 0 orderable stock'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => true,
                    'value' => 0,
                ],
                'orderable_stock_value' => 0,
            ],
            [
                'in_stock' => false,
                'manage_stock' => true,
                'quantity' => 0,
            ],
        ];

        $tests['simple in stock, unlimited, no orderable stock'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => true,
                    'unlimited' => true,
                    'value' => -5,
                ],
            ],
            [
                'in_stock' => true,
                'manage_stock' => false,
                'quantity' => null,
            ],
        ];

        //         With backorder checking
        $tests['simple, value negative or 0, unlimited, backorder enabled'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => true,
                    'value' => 0,
                ],
                'orderable_stock_value' => 0,
                'backorder_enabled' => true,
            ],
            [
                'in_stock' => false,
                'manage_stock' => true,
                'quantity' => 0,
                'backorders' => 'yes'
            ],
        ];

        $tests['simple, value negative or 0, unlimited, backorder disabled'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => true,
                    'value' => 0,
                ],
                'orderable_stock_value' => 0,
                'backorder_enabled' => false,
            ],
            [
                'in_stock' => false,
                'manage_stock' => true,
                'quantity' => 0,
                'backorders' => 'no'
            ],
        ];

        $tests['simple, value positive, unlimited, backorder disabled'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => true,
                    'value' => 5,
                ],
                'orderable_stock_value' => 5,
                'backorder_enabled' => false,
            ],
            [
                'in_stock' => true,
                'manage_stock' => false,
                'quantity' => null,
                'backorders' => 'no'
            ],
        ];

        $tests['simple, value positive, limited, backorder disabled'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => false,
                    'value' => 7,
                ],
                'orderable_stock_value' => 7,
                'backorder_enabled' => false,
            ],
            [
                'in_stock' => true,
                'manage_stock' => true,
                'quantity' => 7,
                'backorders' => 'no'
            ],
        ];

        $tests['simple, value negative or 0, limited, backorder disabled'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => false,
                    'value' => 0,
                ],
                'orderable_stock_value' => 0,
                'backorder_enabled' => false,
            ],
            [
                'in_stock' => false,
                'manage_stock' => true,
                'quantity' => 0,
                'backorders' => 'no'
            ],
        ];

        $tests['simple, value negative or 0, limited, backorder enabled'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => false,
                    'value' => 0,
                ],
                'orderable_stock_value' => 0,
                'backorder_enabled' => true,
            ],
            [
                'in_stock' => false,
                'manage_stock' => true,
                'quantity' => 0,
                'backorders' => 'yes'
            ],
        ];

        return $tests;
    }
}
