<?php
// install.php writes config.php for you. Copy this file only if you prefer manual setup.
return [
    'app_name' => 'Gingersnap Warehouse',
    'timezone' => 'UTC',
    'db' => [
        'driver' => 'mysql',          // 'mysql' for cPanel, 'sqlite' for local testing
        'host' => 'localhost',
        'name' => 'cpaneluser_gingersnap',
        'user' => 'cpaneluser_gsw',
        'pass' => 'change-me',
        'sqlite_path' => __DIR__ . '/data/app.sqlite',
    ],
];
