<?php

return [
    'app_name' => 'GLOW',
    'timezone' => 'Asia/Kolkata',
    'per_page' => 10,
    'db' => [
        // GLOW_DSN env var lets tests/engine_check.php run against an in-memory SQLite DB.
        // Hostinger: dbname and user both carry your account prefix (u123456789_glow / u123456789_glowuser).
        'dsn' => getenv('GLOW_DSN') ?: 'mysql:host=localhost;port=3306;dbname=glow;charset=utf8mb4',
        'user' => getenv('GLOW_DSN') ? null : 'root',
        'pass' => getenv('GLOW_DSN') ? null : '',
    ],
];
