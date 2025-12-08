<?php

return [
    'high_chart' => [
        'bar' => [
            'chart' => [
                'type' => 'bar',
                'style' => [
                    'fontFamily' => 'Cairo , Poppins, sans-serif',
                ]
            ],
            'xAxis' => [
                'categories' => [],
            ],
            'title' => [
                'text' => '',
            ],
            'yAxis' => [
                'title' => [
                    'text' => '',
                ],
            ],
            'series' => [],
        ],
        'line' => [
            'chart' => [
                'type' => 'line',
                'title' => '',
                'style' => [
                    'fontFamily' => 'Cairo , Poppins, sans-serif',
                ],
            ],
            'title' => [
                'text' => '',
            ],
            'xAxis' => [
                'categories' => [],
            ],
            'yAxis' => [
                'title' => [
                    'text' => '',
                ],
            ],
            'series' => [],
        ],
        'spline' => [
            'chart' => [
                'type' => 'spline',
                'title' => '',
                'style' => [
                    'fontFamily' => 'Cairo , Poppins, sans-serif',
                ]
            ],
            'title' => [
                'text' => '',
            ],
            'xAxis' => [
                'categories' => [],
            ],
            'yAxis' => [
                'title' => [
                    'text' => '',
                ],
            ],
            'series' => [],
        ],
        'column' => [
            'chart' => [
                'type' => 'column',
                'style' => [
                    'fontFamily' => 'Cairo , Poppins, sans-serif',
                ]
            ],
            'xAxis' => [
                'categories' => [],// Add Categories here
            ],
            'yAxis' => [
                'title' => [
                    'text' => '',
                ],
            ],
            'title' => [
                'text' => '',
            ],
            'plotOptions' => [
                'column' => [
                    'borderRadius' => '50%',
                    'maxPointWidth' => '25',
                ]
            ],
            'series' => [],// Add Series Here
        ],
        'area' => [
            'chart' => [
                'type' => 'area',
                'title' => '',
                'style' => [
                    'fontFamily' => 'Cairo , Poppins, sans-serif',
                ]
            ],
            'title' => [
                'text' => '',
            ],
            'xAxis' => [
                'categories' => [],
            ],
            'yAxis' => [
                'title' => [
                    'text' => '',
                ],
            ],
            'series' => [],
        ],
        'pie' => [
            'chart' => [
                'type' => 'pie',
                'style' => [
                    'fontFamily' => 'Cairo , Poppins, sans-serif',
                ]
            ],
            'title' => [
                'text' => '',
            ],
            'plotOptions' => [
                'pie' => [
                    'allowPointSelect' => true,
                    'cursor' => 'pointer',
                    'plotOptions' => [
                        'pie' => [
                            'allowPointSelect' => true,
                            'cursor' => 'pointer',
                        ]
                    ],
                ],
                'series' => [
                    'allowPointSelect' => true,
                    'cursor' => 'pointer',
                    'dataLabels' => [
                        [
                            'enabled' => true,
                            'distance' => 20,
                        ],
                        [
                            'enabled' => true,
                            'distance' => -40,
                            'format' => '{point.percentage:.1f}%',
                            'style' => [
                                'fontSize' => '1.2em',
                                'textOutline' => 'none',
                                'opacity' => 0.7,
                            ],
                            'filter' => [
                                'operator' => '>',
                                'property' => 'percentage',
                                'value' => 10,
                            ],
                        ],
                    ],
                ],
            ],
            'series' => []
        ]
    ]
];
