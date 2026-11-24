# Gingersnap Warehouse System

A self-hosted inventory app in plain PHP 8 and MySQL, built to run on ordinary cPanel hosting.
No Composer, Node or SSH needed.

**Features:** dashboard with KPIs and a sales chart, products, stock across locations (warehouse, lab, shop),
production batches with QA release and expiry, sales orders on a pick-pack-ship board, returns,
purchase orders and suppliers, transfers, cycle counts, customers, users with admin/staff roles,
email alerts for low stock and expiry, light and dark themes.

## Install on cPanel

1. In cPanel open **MySQL Databases**. Create a database, create a user, and add the user to the database with all privileges.
2. Open **File Manager** and upload `gingersnap-warehouse.zip` to `public_html` (or a subfolder such as `public_html/stock`). Extract it. You get one folder, `app`; rename it if you like.
3. Visit `https://your-domain/<folder>/install.php`. Enter the database details from step 1, then your admin name, email and password.
4. **Delete `install.php`** from the server when it finishes. Then sign in at `login.php`.
5. Optional daily alerts: in cPanel **Cron Jobs** add a daily job running `/usr/local/bin/php /home/USER/public_html/<folder>/cron/alerts.php`. Set the recipient under Settings → Notifications.

Use HTTPS (cPanel's free AutoSSL is fine). Back up your database from cPanel's **Backup** or phpMyAdmin export.

## How stock works

- Stock is tracked per product per location, and every change is logged in `stock_moves`.
- A new batch starts in **quarantine** and adds nothing to stock until you **Release** it after QA.
- Stock leaves when an order is **shipped**, and enters when a purchase order is **received** or a return is restocked.
- Cycle counts and manual adjustments correct the system to what you counted.

## Local testing

`cd app && php -S 127.0.0.1:8000`, open `/install.php`, and pick **SQLite (testing only)**.
