<?php
// ============================================
// ALISA - Application Configuration
// ============================================

return [
    'db' => [
        'host'    => Env::get('DB_HOST', 'localhost'),
        'name'    => Env::get('DB_NAME', 'silosa'),
        'user'    => Env::get('DB_USER', 'root'),
        'pass'    => Env::get('DB_PASS', ''),
        'charset' => Env::get('DB_CHARSET', 'utf8mb4'),
    ],
    'db_sil2020' => [
        'host'    => Env::get('DB_SIL_HOST', 'localhost'),
        'name'    => Env::get('DB_SIL_NAME', 'sil2020'),
        'user'    => Env::get('DB_SIL_USER', 'root'),
        'pass'    => Env::get('DB_SIL_PASS', ''),
        'charset' => Env::get('DB_SIL_CHARSET', 'utf8mb4'),
    ],
    'app' => [
        'name'    => Env::get('APP_NAME', 'ALISA'),
        'version' => '1.0.0',
        'debug'   => Env::get('APP_DEBUG', false),
        'url'     => Env::get('APP_URL', 'http://localhost/alisa'),
    ],
];
