<?php
/**
 * @author @ct-jensschulze <jens.schulze@commercetools.de>
 */

namespace Commercetools\Sunrise\AppBundle\Model\View;

use Commercetools\Core\Cache\NullCacheAdapter;
use Commercetools\Core\Model\Product\ProductProjection;
use Commercetools\Core\Model\ProductType\ProductType;
use Commercetools\Sunrise\AppBundle\Model\Config;

class ProductModelTest extends \PHPUnit_Framework_TestCase
{
    public function getVariantsData()
    {
        $productType = [
            'id' => 'test-id',
            'name' => 'test-type',
            'attributes' => [
                [
                    'name' => 'size',
                    'type' => [
                        'name' => 'text'
                    ]
                ],
                [
                    'name' => 'color',
                    'type' => [
                        'name' => 'enum',
                        'values' => [
                            [
                                'key' => 'black',
                                'label' => 'Black'
                            ],
                            [
                                'key' => 'white',
                                'label' => 'White'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return [
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'size',
                                'value' => '36'
                            ],
                            [
                                'name' => 'color',
                                'value' => [
                                    'key' => 'black',
                                    'label' => 'Black'
                                ]
                            ]
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'black',
                                        'label' => 'Black'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '36'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-4',
                'expectedModel' => [
                    'variants' => [
                        'Black-36' => 1,
                        'Black-38' => 2,
                        'White-36' => 3,
                        'White-38' => 4,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                        1 => 'size',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => false,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                'Black' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                                'White' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                            ],
                        ],
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => false,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                36 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                                38 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'color',
                                'value' => [
                                    'key' => 'black',
                                    'label' => 'Black'
                                ]
                            ],
                            [
                                'name' => 'size',
                                'value' => '36'
                            ],
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'black',
                                        'label' => 'Black'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                            'attributes' => [
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ],
                                [
                                    'name' => 'size',
                                    'value' => '36'
                                ],
                            ]
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-4',
                'expectedModel' => [
                    'variants' => [
                        'Black-36' => 1,
                        'Black-38' => 2,
                        'White-36' => 3,
                        'White-38' => 4,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                        1 => 'size',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => false,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                'Black' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                                'White' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                            ],
                        ],
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => false,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                36 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                                38 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'size',
                                'value' => '36'
                            ],
                            [
                                'name' => 'color',
                                'value' => [
                                    'key' => 'black',
                                    'label' => 'Black'
                                ]
                            ]
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'black',
                                        'label' => 'Black'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '36'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-1',
                'expectedModel' => [
                    'variants' => [
                        'Black-36' => 1,
                        'Black-38' => 2,
                        'White-36' => 3,
                        'White-38' => 4,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                        1 => 'size',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => true,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => false,
                                ],
                            ],
                            'selectData' => [
                                'Black' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                                'White' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                            ],
                        ],
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => true,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => false,
                                ],
                            ],
                            'selectData' => [
                                36 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                                38 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'size',
                                'value' => '36'
                            ],
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'black',
                                        'label' => 'Black'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '36'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-4',
                'expectedModel' => [
                    'variants' => [
                        'Black-38' => 2,
                        'White-36' => 3,
                        'White-38' => 4,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                        1 => 'size',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => false,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                'Black' => [
                                    'size' => [
                                        0 => '38',
                                    ],
                                ],
                                'White' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                            ],
                        ],
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => false,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                36 => [
                                    'color' => [
                                        0 => 'White',
                                    ],
                                ],
                                38 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'color',
                                'value' => [
                                    'key' => 'black',
                                    'label' => 'Black'
                                ]
                            ]
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'black',
                                        'label' => 'Black'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '36'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-4',
                'expectedModel' => [
                    'variants' => [
                        'Black-38' => 2,
                        'White-36' => 3,
                        'White-38' => 4,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                        1 => 'size',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => false,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                'Black' => [
                                    'size' => [
                                        0 => '38',
                                    ],
                                ],
                                'White' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                            ],
                        ],
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => false,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                36 => [
                                    'color' => [
                                        0 => 'White',
                                    ],
                                ],
                                38 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'size',
                                'value' => '36'
                            ],
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                            ]
                        ],
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-2',
                'expectedModel' => [
                    'variants' => [
                        '36' => 1,
                        '38' => 2
                    ],
                    'variantIdentifiers' => [
                        0 => 'size',
                    ],
                    'attributes' => [
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => false,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'color',
                                'value' => [
                                    'key' => 'black',
                                    'label' => 'Black'
                                ]
                            ]
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-2',
                'expectedModel' => [
                    'variants' => [
                        'Black' => 1,
                        'White' => 2,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => false,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                        ]
                    ],
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-1',
                'expectedModel' => [
                    'variants' => [],
                    'variantIdentifiers' => [
                    ],
                    'attributes' => [
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                        'attributes' => [
                            [
                                'name' => 'size',
                                'value' => '36'
                            ],
                            [
                                'name' => 'color',
                                'value' => [
                                    'key' => 'black',
                                    'label' => 'Black'
                                ]
                            ]
                        ]
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'black',
                                        'label' => 'Black'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '36'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ],
                        [
                            'id' => 5,
                            'sku' => 'VARIANT-5',
                            'attributes' => [
                                [
                                    'name' => 'size',
                                    'value' => '38'
                                ],
                                [
                                    'name' => 'color',
                                    'value' => [
                                        'key' => 'white',
                                        'label' => 'White'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-5',
                'expectedModel' => [
                    'variants' => [
                        'Black-36' => 1,
                        'Black-38' => 2,
                        'White-36' => 3,
                        'White-38' => 5,
                    ],
                    'variantIdentifiers' => [
                        0 => 'color',
                        1 => 'size',
                    ],
                    'attributes' => [
                        'color' => [
                            'key' => 'color',
                            'name' => 'color',
                            'list' => [
                                'Black' => [
                                    'label' => 'Black',
                                    'value' => 'Black',
                                    'selected' => false,
                                ],
                                'White' => [
                                    'label' => 'White',
                                    'value' => 'White',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                'Black' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                                'White' => [
                                    'size' => [
                                        0 => '36',
                                        1 => '38',
                                    ],
                                ],
                            ],
                        ],
                        'size' => [
                            'key' => 'size',
                            'name' => 'size',
                            'list' => [
                                36 => [
                                    'label' => '36',
                                    'value' => '36',
                                    'selected' => false,
                                ],
                                38 => [
                                    'label' => '38',
                                    'value' => '38',
                                    'selected' => true,
                                ],
                            ],
                            'selectData' => [
                                36 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                                38 => [
                                    'color' => [
                                        0 => 'Black',
                                        1 => 'White',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'productData' => [
                    'productType' => [
                        'typeId' => 'product-type',
                        'id' => 'test-id2'
                    ],
                    'masterVariant' => [
                        'id' => 1,
                        'sku' => 'VARIANT-1',
                    ],
                    'variants' => [
                        [
                            'id' => 2,
                            'sku' => 'VARIANT-2',
                        ],
                        [
                            'id' => 3,
                            'sku' => 'VARIANT-3',
                        ],
                        [
                            'id' => 4,
                            'sku' => 'VARIANT-4',
                        ],
                        [
                            'id' => 5,
                            'sku' => 'VARIANT-5',
                        ]
                    ]
                ],
                'productTypeData' => $productType,
                'selectSku' => 'VARIANT-5',
                'expectedModel' => [
                    'variants' => [],
                    'variantIdentifiers' => [],
                    'attributes' => [],
                ],
            ],
        ];
    }
    /**
     * @dataProvider getVariantsData
     * @param $productData
     * @param $productTypeData
     * @param $selectSku
     * @param $expectedModel
     */
    public function testVariantsSelector($productData, $productTypeData, $selectSku, $expectedModel)
    {
        /**
         * @var ProductModel $model
         */
        $urlGenerator = $this->getMock('\Symfony\Component\Routing\Generator\UrlGenerator', [], [], '', false);
        $productTypeRepository = $this->getMock('\Commercetools\Sunrise\Model\Repository\ProductTypeRepository', [], [], '', false);
        $config = new Config([
            'sunrise' => [
                'products' => [
                    'variantsSelector' => [
                        'test-type' => ['color', 'size']
                    ]
                ]
            ]
        ]);
        $model = new ProductModel(
            new NullCacheAdapter(),
            $config,
            $productTypeRepository,
            $urlGenerator
        );
        $product = ProductProjection::fromArray($productData);
        $productType = ProductType::fromArray($productTypeData);
        list($attributes, $variantKeys, $variantIdentifiers) = $model->getVariantSelectors($product, $productType, $selectSku);

        $variants = [
            'variants' => $variantKeys,
            'variantIdentifiers' => $variantIdentifiers,
            'attributes' => $attributes
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedModel), json_encode($variants));
    }

    public function testVariantsSelectOrder()
    {
        $productData = [
            'productType' => [
                'typeId' => 'product-type',
                'id' => 'test-id'
            ],
            'masterVariant' => [
                'id' => 1,
                'sku' => 'VARIANT-1',
                'attributes' => [
                    [
                        'name' => 'size',
                        'value' => '36'
                    ],
                    [
                        'name' => 'color',
                        'value' => [
                            'key' => 'black',
                            'label' => 'Black'
                        ]
                    ],
                ]
            ],
            'variants' => [
                [
                    'id' => 2,
                    'sku' => 'VARIANT-2',
                    'attributes' => [
                        [
                            'name' => 'size',
                            'value' => '38'
                        ],
                        [
                            'name' => 'color',
                            'value' => [
                                'key' => 'black',
                                'label' => 'Black'
                            ]
                        ]
                    ]
                ],
                [
                    'id' => 3,
                    'sku' => 'VARIANT-3',
                    'attributes' => [
                        [
                            'name' => 'color',
                            'value' => [
                                'key' => 'white',
                                'label' => 'White'
                            ]
                        ],
                        [
                            'name' => 'size',
                            'value' => '36'
                        ],
                    ]
                ],
                [
                    'id' => 4,
                    'sku' => 'VARIANT-4',
                    'attributes' => [
                        [
                            'name' => 'size',
                            'value' => '38'
                        ],
                        [
                            'name' => 'color',
                            'value' => [
                                'key' => 'white',
                                'label' => 'White'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $productTypeData = [
            'id' => 'test-id',
            'name' => 'test-type',
            'attributes' => [
                [
                    'name' => 'size',
                    'type' => [
                        'name' => 'text'
                    ]
                ],
                [
                    'name' => 'color',
                    'type' => [
                        'name' => 'enum',
                        'values' => [
                            [
                                'key' => 'black',
                                'label' => 'Black'
                            ],
                            [
                                'key' => 'white',
                                'label' => 'White'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $selectSku = 'VARIANT-4';
        $expectedModel = [
            'variants' => [
                '36-Black' => 1,
                '38-Black' => 2,
                '36-White' => 3,
                '38-White' => 4,
            ],
            'variantIdentifiers' => [
                0 => 'size',
                1 => 'color',
            ],
            'attributes' => [
                'color' => [
                    'key' => 'color',
                    'name' => 'color',
                    'list' => [
                        'Black' => [
                            'label' => 'Black',
                            'value' => 'Black',
                            'selected' => false,
                        ],
                        'White' => [
                            'label' => 'White',
                            'value' => 'White',
                            'selected' => true,
                        ],
                    ],
                    'selectData' => [
                        'Black' => [
                            'size' => [
                                0 => '36',
                                1 => '38',
                            ],
                        ],
                        'White' => [
                            'size' => [
                                0 => '36',
                                1 => '38',
                            ],
                        ],
                    ],
                ],
                'size' => [
                    'key' => 'size',
                    'name' => 'size',
                    'list' => [
                        36 => [
                            'label' => '36',
                            'value' => '36',
                            'selected' => false,
                        ],
                        38 => [
                            'label' => '38',
                            'value' => '38',
                            'selected' => true,
                        ],
                    ],
                    'selectData' => [
                        36 => [
                            'color' => [
                                0 => 'Black',
                                1 => 'White',
                            ],
                        ],
                        38 => [
                            'color' => [
                                0 => 'Black',
                                1 => 'White',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        /**
         * @var ProductModel $model
         */
        $urlGenerator = $this->getMock('\Symfony\Component\Routing\Generator\UrlGenerator', [], [], '', false);
        $productTypeRepository = $this->getMock('\Commercetools\Sunrise\Model\Repository\ProductTypeRepository', [], [], '', false);
        $config = new Config([
            'sunrise' => [
                'products' => [
                    'variantsSelector' => [
                        'test-type' => ['size', 'color']
                    ]
                ]
            ]
        ]);
        $model = new ProductModel(
            new NullCacheAdapter(),
            $config,
            $productTypeRepository,
            $urlGenerator
        );
        $product = ProductProjection::fromArray($productData);
        $productType = ProductType::fromArray($productTypeData);
        list($attributes, $variantKeys, $variantIdentifiers) = $model->getVariantSelectors($product, $productType, $selectSku);

        $variants = [
            'variants' => $variantKeys,
            'variantIdentifiers' => $variantIdentifiers,
            'attributes' => $attributes
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($expectedModel), json_encode($variants));

    }
}
