<?php
// Copy to config.local.php and fill in — or let install/install.php create it.
// config.local.php must NEVER be committed or shared. It is protected by app/.htaccess.
return [
    'db' => [
        'host' => 'localhost',
        'name' => 'cpanelprefix_barangay',   // cPanel → MySQL Databases
        'user' => 'cpanelprefix_brgyuser',
        'pass' => '',                        // set a strong password
    ],
    'base_url' => '/barangay',               // '' if installed at the domain root
    'app_salt' => '',                        // random string; installer generates one
    'debug'    => false,                     // true only on a dev machine
];
