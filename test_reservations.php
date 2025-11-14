<?php

/**
 * Script de pruebas para validar el sistema de reservas
 * Ejecuta 20 pruebas diferentes con variaciones en:
 * - Servicios diferentes
 * - Con y sin addons
 * - Diferentes zonas
 * - Con y sin promo codes
 * - Diferentes cantidades de niños
 * - Diferentes tipos de eventos
 */

// Configuración
$baseUrl = 'http://localhost/JamWitjJamie/api/home/reservation';
$results = [];
$totalPassed = 0;
$totalFailed = 0;

// Función auxiliar para hacer requests
function makeRequest($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'httpCode' => $httpCode,
        'response' => json_decode($response, true),
        'rawResponse' => $response
    ];
}

// Función para generar datos base de prueba
function generateBaseData($testNumber) {
    return [
        'session_id' => 'test-session-' . $testNumber,
        'customer' => [
            'firstName' => 'Test',
            'lastName' => 'User' . $testNumber,
            'email' => "test{$testNumber}@example.com",
            'phone' => '555-000' . str_pad($testNumber, 4, '0', STR_PAD_LEFT),
            'eventType' => 'Birthday Party',
            'metropolitanArea' => 'Los Angeles',
            'zipCode' => '90001',
            'eventDateTime' => date('Y-m-d', strtotime('+30 days')),
            'childrenRange' => '1-10 kids'
        ],
        'zipcode' => [
            'id' => '01J9XXXXXXXXXXXXXXXXXXXX',
            'zipcode' => '90001',
            'zone_type' => 'standard'
        ],
        'service' => [
            'id' => '01J9XXXXXXXXXXXXXXXXXXXX',
            'name' => 'Classic Jam',
            'amount' => 350.00,
            'performers_count' => 1,
            'duration_hours' => 0.75,
            'max_kids_included' => 40,
            'extra_child_fee' => 10.00
        ],
        'addons' => [],
        'subtotal' => [
            'subtotal' => 350.00,
            'servicePrice' => 350.00,
            'addonsTotal' => 0,
            'extraChildrenTotal' => 0,
            'travelFee' => 0,
            'discount' => 0
        ],
        'information' => [
            'fullAddress' => '123 Test St, Los Angeles, CA 90001',
            'startTime' => '14:00',
            'eventDate' => date('Y-m-d', strtotime('+30 days')),
            'arrivalParkingInstructions' => 'Park in front',
            'entertainmentStartTime' => '14:30',
            'birthdayChildName' => 'Test Child',
            'childAge' => 5,
            'ageRange' => '4-6 years',
            'songRequests' => 'Happy Birthday',
            'happyBirthdayRequest' => true,
            'instructions' => 'Test instructions'
        ]
    ];
}

// Definir 20 pruebas diferentes
$tests = [
    // Test 1: Reserva básica sin addons
    [
        'name' => 'Test 1: Reserva básica Classic Jam sin addons',
        'data' => generateBaseData(1)
    ],

    // Test 2: Classic Jam Duo
    [
        'name' => 'Test 2: Classic Jam Duo (2 performers)',
        'data' => array_merge(generateBaseData(2), [
            'service' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXXY',
                'name' => 'Classic Jam Duo',
                'amount' => 475.00,
                'performers_count' => 2,
                'duration_hours' => 0.75,
                'max_kids_included' => 40,
                'extra_child_fee' => 10.00
            ],
            'subtotal' => [
                'subtotal' => 475.00,
                'servicePrice' => 475.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 3: Con addon de 15 minutos
    [
        'name' => 'Test 3: Classic Jam + 15 minutos extra',
        'data' => array_merge(generateBaseData(3), [
            'addons' => [
                [
                    'id' => 'addon-15min-xxxxxx',
                    'name' => '15 minutes',
                    'base_price' => 50.00,
                    'quantity' => 1,
                    'is_referral_service' => false
                ]
            ],
            'subtotal' => [
                'subtotal' => 400.00,
                'servicePrice' => 350.00,
                'addonsTotal' => 50.00,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 4: Con Jukebox Live 1h, 1 performer
    [
        'name' => 'Test 4: Classic Jam + Jukebox Live 1h',
        'data' => array_merge(generateBaseData(4), [
            'addons' => [
                [
                    'id' => 'addon-jukebox-1h1p',
                    'name' => 'Jukebox Live',
                    'base_price' => 375.00,
                    'selectedPrice' => 375.00,
                    'quantity' => 1,
                    'is_referral_service' => false,
                    'price_type' => 'jukebox',
                    'selectedOption' => '1h-1p'
                ]
            ],
            'subtotal' => [
                'subtotal' => 725.00,
                'servicePrice' => 350.00,
                'addonsTotal' => 375.00,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 5: Con 25+ niños (50 niños exactos)
    [
        'name' => 'Test 5: 25+ niños (50 exactos) - debe cobrar extra',
        'data' => array_merge(generateBaseData(5), [
            'customer' => array_merge(generateBaseData(5)['customer'], [
                'childrenRange' => '25+ kids',
                'exactChildrenCount' => 50
            ]),
            'subtotal' => [
                'subtotal' => 450.00, // 350 + (10 niños extra * 10)
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 100.00,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 6: Zona con travel fee
    [
        'name' => 'Test 6: Zona con travel fee ($50)',
        'data' => array_merge(generateBaseData(6), [
            'zipcode' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXXY',
                'zipcode' => '90210',
                'zone_type' => 'travel_fee',
                'travel_fee' => 50.00
            ],
            'subtotal' => [
                'subtotal' => 400.00,
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 50.00,
                'discount' => 0
            ]
        ])
    ],

    // Test 7: Con promo code (10% descuento)
    [
        'name' => 'Test 7: Con promo code 10% descuento',
        'data' => array_merge(generateBaseData(7), [
            'subtotal' => [
                'subtotal' => 315.00, // 350 - 35
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 35.00,
                'promoCode' => 'TEST10',
                'promoCodeData' => [
                    'code' => 'TEST10',
                    'discount_percentage' => 10
                ]
            ]
        ])
    ],

    // Test 8: Junior Jammer Mashup
    [
        'name' => 'Test 8: Junior Jammer Mashup',
        'data' => array_merge(generateBaseData(8), [
            'service' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXYZ',
                'name' => 'Junior Jammer Mashup',
                'amount' => 525.00,
                'performers_count' => 2,
                'duration_hours' => 0.75,
                'max_kids_included' => 40,
                'extra_child_fee' => 10.00
            ],
            'subtotal' => [
                'subtotal' => 525.00,
                'servicePrice' => 525.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 9: Eras Jam
    [
        'name' => 'Test 9: Eras Jam',
        'data' => array_merge(generateBaseData(9), [
            'service' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXZA',
                'name' => 'Eras Jam',
                'amount' => 675.00,
                'performers_count' => 2,
                'duration_hours' => 0.75,
                'max_kids_included' => 40,
                'extra_child_fee' => 10.00
            ],
            'subtotal' => [
                'subtotal' => 675.00,
                'servicePrice' => 675.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 10: Big Kids Party
    [
        'name' => 'Test 10: Big Kids Party',
        'data' => array_merge(generateBaseData(10), [
            'service' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXZB',
                'name' => 'Big Kids Party',
                'amount' => 675.00,
                'performers_count' => 2,
                'duration_hours' => 0.75,
                'max_kids_included' => 40,
                'extra_child_fee' => 10.00
            ],
            'subtotal' => [
                'subtotal' => 675.00,
                'servicePrice' => 675.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 11: Evento tipo "Event" en lugar de "Birthday Party"
    [
        'name' => 'Test 11: Tipo de evento "Event"',
        'data' => array_merge(generateBaseData(11), [
            'customer' => array_merge(generateBaseData(11)['customer'], [
                'eventType' => 'Event'
            ])
        ])
    ],

    // Test 12: Evento tipo "One Time Jam Session"
    [
        'name' => 'Test 12: Tipo de evento "One Time Jam Session"',
        'data' => array_merge(generateBaseData(12), [
            'customer' => array_merge(generateBaseData(12)['customer'], [
                'eventType' => 'One Time Jam Session'
            ])
        ])
    ],

    // Test 13: Combinación compleja - servicio + addon + travel fee + extra niños
    [
        'name' => 'Test 13: Combinación compleja (servicio + addon + travel + extras)',
        'data' => array_merge(generateBaseData(13), [
            'customer' => array_merge(generateBaseData(13)['customer'], [
                'childrenRange' => '25+ kids',
                'exactChildrenCount' => 45
            ]),
            'zipcode' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXXY',
                'zipcode' => '90210',
                'zone_type' => 'travel_fee',
                'travel_fee' => 50.00
            ],
            'addons' => [
                [
                    'id' => 'addon-15min-xxxxxx',
                    'name' => '15 minutes',
                    'base_price' => 50.00,
                    'quantity' => 1,
                    'is_referral_service' => false
                ]
            ],
            'subtotal' => [
                'subtotal' => 500.00, // 350 + 50 (addon) + 50 (travel) + 50 (5 extra kids)
                'servicePrice' => 350.00,
                'addonsTotal' => 50.00,
                'extraChildrenTotal' => 50.00,
                'travelFee' => 50.00,
                'discount' => 0
            ]
        ])
    ],

    // Test 14: Con múltiples addons
    [
        'name' => 'Test 14: Múltiples addons (15min + Jukebox)',
        'data' => array_merge(generateBaseData(14), [
            'addons' => [
                [
                    'id' => 'addon-15min-xxxxxx',
                    'name' => '15 minutes',
                    'base_price' => 50.00,
                    'quantity' => 1,
                    'is_referral_service' => false
                ],
                [
                    'id' => 'addon-jukebox-1h1p',
                    'name' => 'Jukebox Live',
                    'base_price' => 375.00,
                    'selectedPrice' => 375.00,
                    'quantity' => 1,
                    'is_referral_service' => false,
                    'price_type' => 'jukebox',
                    'selectedOption' => '1h-1p'
                ]
            ],
            'subtotal' => [
                'subtotal' => 775.00, // 350 + 50 + 375
                'servicePrice' => 350.00,
                'addonsTotal' => 425.00,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 15: Jukebox Live 2h con 2 performers
    [
        'name' => 'Test 15: Classic Jam + Jukebox Live 2h, 2 performers',
        'data' => array_merge(generateBaseData(15), [
            'addons' => [
                [
                    'id' => 'addon-jukebox-2h2p',
                    'name' => 'Jukebox Live',
                    'base_price' => 850.00,
                    'selectedPrice' => 850.00,
                    'quantity' => 1,
                    'is_referral_service' => false,
                    'price_type' => 'jukebox',
                    'selectedOption' => '2h-2p'
                ]
            ],
            'subtotal' => [
                'subtotal' => 1200.00, // 350 + 850
                'servicePrice' => 350.00,
                'addonsTotal' => 850.00,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 16: 11-24 kids (punto medio = 17 niños)
    [
        'name' => 'Test 16: Rango 11-24 kids (no extra fee)',
        'data' => array_merge(generateBaseData(16), [
            'customer' => array_merge(generateBaseData(16)['customer'], [
                'childrenRange' => '11-24 kids'
            ]),
            'subtotal' => [
                'subtotal' => 350.00,
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 17: Con promo code y travel fee
    [
        'name' => 'Test 17: Promo code + travel fee (descuento no aplica a travel)',
        'data' => array_merge(generateBaseData(17), [
            'zipcode' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXXY',
                'zipcode' => '90210',
                'zone_type' => 'travel_fee',
                'travel_fee' => 50.00
            ],
            'subtotal' => [
                'subtotal' => 365.00, // (350 - 35) + 50
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 50.00,
                'discount' => 35.00,
                'promoCode' => 'TEST10'
            ]
        ])
    ],

    // Test 18: Máximo de niños incluidos (40)
    [
        'name' => 'Test 18: Exactamente 40 niños (sin extra fee)',
        'data' => array_merge(generateBaseData(18), [
            'customer' => array_merge(generateBaseData(18)['customer'], [
                'childrenRange' => '25+ kids',
                'exactChildrenCount' => 40
            ]),
            'subtotal' => [
                'subtotal' => 350.00,
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 19: Con addon referral (Custom Song)
    [
        'name' => 'Test 19: Con addon referral service (sin costo)',
        'data' => array_merge(generateBaseData(19), [
            'addons' => [
                [
                    'id' => 'addon-custom-song',
                    'name' => 'Custom Song',
                    'base_price' => 0,
                    'quantity' => 1,
                    'is_referral_service' => true
                ]
            ],
            'subtotal' => [
                'subtotal' => 350.00,
                'servicePrice' => 350.00,
                'addonsTotal' => 0,
                'extraChildrenTotal' => 0,
                'travelFee' => 0,
                'discount' => 0
            ]
        ])
    ],

    // Test 20: Caso completo máximo - todo combinado
    [
        'name' => 'Test 20: Caso completo (servicio caro + addons + travel + extras + promo)',
        'data' => array_merge(generateBaseData(20), [
            'customer' => array_merge(generateBaseData(20)['customer'], [
                'childrenRange' => '25+ kids',
                'exactChildrenCount' => 60
            ]),
            'service' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXZB',
                'name' => 'Big Kids Party',
                'amount' => 675.00,
                'performers_count' => 2,
                'duration_hours' => 0.75,
                'max_kids_included' => 40,
                'extra_child_fee' => 10.00
            ],
            'zipcode' => [
                'id' => '01J9XXXXXXXXXXXXXXXXXXXY',
                'zipcode' => '90210',
                'zone_type' => 'travel_fee',
                'travel_fee' => 80.00
            ],
            'addons' => [
                [
                    'id' => 'addon-15min-xxxxxx',
                    'name' => '15 minutes',
                    'base_price' => 80.00,
                    'quantity' => 1,
                    'is_referral_service' => false
                ],
                [
                    'id' => 'addon-jukebox-1h2p',
                    'name' => 'Jukebox Live',
                    'base_price' => 500.00,
                    'selectedPrice' => 500.00,
                    'quantity' => 1,
                    'is_referral_service' => false,
                    'price_type' => 'jukebox',
                    'selectedOption' => '1h-2p'
                ]
            ],
            'subtotal' => [
                // (675 + 80 + 500 + 200 - 145.5) + 80 = 1389.5
                'subtotal' => 1389.50,
                'servicePrice' => 675.00,
                'addonsTotal' => 580.00, // 80 + 500
                'extraChildrenTotal' => 200.00, // 20 kids * 10
                'travelFee' => 80.00,
                'discount' => 145.50, // 10% de (675 + 580 + 200)
                'promoCode' => 'TEST10'
            ]
        ])
    ]
];

// Ejecutar pruebas
echo "=================================\n";
echo "INICIANDO PRUEBAS DE RESERVAS\n";
echo "=================================\n\n";
echo "URL: $baseUrl\n";
echo "Total de pruebas: " . count($tests) . "\n\n";

foreach ($tests as $index => $test) {
    echo "-----------------------------------\n";
    echo "Ejecutando: {$test['name']}\n";
    echo "-----------------------------------\n";

    $result = makeRequest($baseUrl, $test['data']);

    $status = $result['httpCode'] == 201 ? 'PASSED' : 'FAILED';
    $statusSymbol = $status == 'PASSED' ? '✓' : '✗';

    if ($status == 'PASSED') {
        $totalPassed++;
    } else {
        $totalFailed++;
    }

    $results[] = [
        'name' => $test['name'],
        'status' => $status,
        'httpCode' => $result['httpCode'],
        'response' => $result['response']
    ];

    echo "Status: $statusSymbol $status (HTTP {$result['httpCode']})\n";

    if ($status == 'PASSED' && isset($result['response']['data'])) {
        $data = $result['response']['data'];
        if (isset($data['reservation'])) {
            echo "Reservation ID: {$data['reservation']->id}\n";
            echo "Grand Total: $" . number_format($data['calculation']['grand_total'], 2) . "\n";
        }
    } else {
        echo "Error: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
        if (!empty($result['rawResponse'])) {
            echo "Response: " . substr($result['rawResponse'], 0, 200) . "...\n";
        }
    }

    echo "\n";
}

// Resumen final
echo "=================================\n";
echo "RESUMEN DE PRUEBAS\n";
echo "=================================\n";
echo "Total: " . count($tests) . "\n";
echo "Pasadas: $totalPassed (" . round(($totalPassed / count($tests)) * 100, 2) . "%)\n";
echo "Fallidas: $totalFailed (" . round(($totalFailed / count($tests)) * 100, 2) . "%)\n\n";

if ($totalFailed > 0) {
    echo "PRUEBAS FALLIDAS:\n";
    echo "-----------------------------------\n";
    foreach ($results as $result) {
        if ($result['status'] == 'FAILED') {
            echo "✗ {$result['name']}\n";
            echo "  HTTP {$result['httpCode']}\n";
            echo "  Error: " . ($result['response']['message'] ?? 'Unknown') . "\n\n";
        }
    }
}

echo "=================================\n";
