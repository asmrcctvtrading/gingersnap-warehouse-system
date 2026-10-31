# Local Installation on Windows (XAMPP)

For development, trying the system, or a local kiosk server. For the live site use the cPanel steps in the README.

1. Install XAMPP with PHP 8.0+ and MySQL/MariaDB. Start **Apache** and **MySQL** in the XAMPP control panel.
2. Copy the `barangay-locator` folder to `C:\xampp\htdocs\barangay`.
3. In phpMyAdmin (`http://localhost/phpmyadmin`) create a database `barangay_locator` (utf8mb4_unicode_ci). The default `root` user without password works locally only; for anything shared create a dedicated user.
4. Make sure `extension=pdo_mysql`, `extension=mbstring` and `extension=fileinfo` are enabled in `C:\xampp\php\php.ini`; restart Apache.
5. Open `http://localhost/barangay/install/install.php`. Host `localhost`, base URL `/barangay`. Tick sample data to explore.
6. Delete `C:\xampp\htdocs\barangay\install` when finished. Admin login: `http://localhost/barangay/admin/login.php`.
7. Tests: in PowerShell, `C:\xampp\php\php.exe tests\run_tests.php`. For DB tests create a database named like `barangay_test` and run `C:\xampp\php\php.exe tests\db_tests.php localhost barangay_test root ""`.

Geolocation on `http://localhost` works in modern browsers; on other hostnames it needs HTTPS.
