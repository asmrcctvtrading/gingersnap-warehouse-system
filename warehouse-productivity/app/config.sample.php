<?php
// Copy to config.local.php and fill in — or let install/install.php create it.
// config.local.php must NEVER be committed, shared or placed in a public folder. It is protected by app/.htaccess.
// Where the host allows, keep the whole app/ folder (or just this file) above public_html and point bootstrap.php at it.
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'cpanelprefix_wpms',        // dedicated WPMS database - NEVER the Warehouse Inventory System database
        'user' => 'cpanelprefix_wpmsuser',
        'pass' => '',                         // set a strong password
    ],
    'base_url'    => '/warehouse-productivity', // verify against the real document root before deploy
    'app_salt'    => '',                      // random string; installer generates one
    'storage_dir' => '',                      // absolute path for imports/logs/backups; blank = <app>/storage. Prefer a folder above public_html
    'timezone'    => 'Asia/Manila',
    'debug'       => false,                   // true only on a dev machine
];
