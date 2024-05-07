<?php

namespace StoreKeeper\StoreKeeper\Test\Unit;

use PHPUnit\Framework\TestCase;

class SimpleProductStockTest extends TestCase
{
    /**
     * @return void
     */
    public function testStock(): void
    {
        $stockCases = $this->getStockCases();

        foreach ($stockCases as $label => $data) {
            //Mapping data from Testcases
            $result = $data[0];
            $expected = $data[1];
            $sourceItemData = [];

            //Calculating stock data
            $product_stock_value = (array_key_exists('orderable_stock_value', $result)) ? $result['orderable_stock_value'] : null;
            $product_stock_unlimited = $result['product_stock']['unlimited'];
            $backorder_enabled = (array_key_exists('backorder_enabled', $result)) ? $result['backorder_enabled'] : null;
            $in_stock = null === $product_stock_value || $product_stock_value > 0;

            if ($product_stock_unlimited === true && $in_stock) {
                $sourceItemData['manage_stock'] = 0;
            } elseif ($product_stock_unlimited === true && !$in_stock) {
                $sourceItemData['manage_stock'] = 1;
            } else {
                $sourceItemData['manage_stock'] = 1;
            }

            if ($backorder_enabled === true) {
                $sourceItemData['backorders'] = true;
                $sourceItemData['use_config_backorders'] = false;
            } elseif ($backorder_enabled === false) {
                $sourceItemData['backorders'] = false;
                $sourceItemData['use_config_backorders'] = false;
            } else {
                $sourceItemData['use_config_backorders'] = true;
            }

            $stock_quantity = $sourceItemData['manage_stock'] ? $product_stock_value : null;

            if (!is_null($stock_quantity) && $stock_quantity < 0) {
                $stock_quantity = 0;
            }

            $sourceItemData['quantity'] = $stock_quantity;

            //Formatting output array data
            $output = [
                'in_stock' => $in_stock,
                'manage_stock' => (bool)$sourceItemData['manage_stock'],
                'quantity' => $sourceItemData['quantity'],

            ];
            if (array_key_exists('backorders', $sourceItemData)) {
                $output['backorders'] = ($sourceItemData['backorders'] === true) ? 'yes' : 'no';
            }

            //TODO Assert output array data and expected data
            $this->assertEquals($output, $expected);
        }
    }

    public function getStockCases(): array
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

        $tests['simple product in stock, unlimited, has positive orderable stock'] = [
            [
                'type' => 'simple',
                'product_stock' => [
                    'in_stock' => false,
                    'unlimited' => true,
                    'value' => 5,
                ],
                'orderable_stock_value' => 5,
            ],
            [
                'in_stock' => true,
                'manage_stock' => false,
                'quantity' => null,
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
