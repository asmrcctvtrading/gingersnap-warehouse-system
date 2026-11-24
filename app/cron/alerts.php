<?php
// Run from cPanel > Cron Jobs, e.g. once a day:
//   /usr/local/bin/php /home/USER/public_html/gingersnap/cron/alerts.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/alerts.php';
echo send_alerts(), "\n";
