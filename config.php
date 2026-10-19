<?php


return [
    'app_name' => 'GLOW',
    'timezone' => 'Asia/Kolkata',
    'per_page' => 10,
    'db' => [
        // GLOW_DSN env var lets tests/engine_check.php run against an in-memory SQLite DB.
        'dsn' => getenv('GLOW_DSN') ?: 'mysql:localhost;port=3306;dbname=glow;charset=utf8mb4',
		'user' => getenv('GLOW_DSN') ? null : 'root',
		'pass' => getenv('GLOW_DSN') ? null : '', 

    ],
];
// return [
//     'app_name' => 'GLOW',
//     'timezone' => 'Asia/Kolkata',
//     'per_page' => 10,
//     'db' => [
//         // GLOW_DSN env var lets tests/engine_check.php run against an in-memory SQLite DB.
//         'dsn' => getenv('GLOW_DSN') ?: 'mysql:host=sql211.infinityfree.com;port=3306;dbname=if0_43101847_gold;charset=utf8mb4',
// 		'user' => getenv('GLOW_DSN') ? null : 'if0_43101847',
// 		'pass' => getenv('GLOW_DSN') ? null : 'kQZRHVDGNToHGN', 

//     ],
// ];
