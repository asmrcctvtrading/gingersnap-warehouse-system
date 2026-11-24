<?php
defined('APP') || define('APP', 1);

/** Returns CREATE TABLE statements for the chosen driver. */
function schema_sql(string $driver): array
{
    $pk = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
    $tail = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    $t = [
        'users' => "id $pk, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL DEFAULT 'staff', created_at VARCHAR(19) NOT NULL",
        'locations' => "id $pk, name VARCHAR(120) NOT NULL, type VARCHAR(30) NOT NULL DEFAULT 'warehouse'",
        'products' => "id $pk, sku VARCHAR(60) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL, category VARCHAR(80) NOT NULL DEFAULT '', unit VARCHAR(20) NOT NULL DEFAULT 'pcs', cost DECIMAL(12,2) NOT NULL DEFAULT 0, price DECIMAL(12,2) NOT NULL DEFAULT 0, reorder_level INT NOT NULL DEFAULT 0, active TINYINT NOT NULL DEFAULT 1",
        'stock' => "id $pk, product_id INT NOT NULL, location_id INT NOT NULL, qty INT NOT NULL DEFAULT 0, UNIQUE (product_id, location_id)",
        'stock_moves' => "id $pk, product_id INT NOT NULL, location_id INT NOT NULL, delta INT NOT NULL, reason VARCHAR(40) NOT NULL, ref VARCHAR(60) NOT NULL DEFAULT '', user_id INT NULL, created_at VARCHAR(19) NOT NULL",
        'batches' => "id $pk, product_id INT NOT NULL, lot_no VARCHAR(60) NOT NULL, location_id INT NOT NULL, qty INT NOT NULL, expiry_date VARCHAR(10) NULL, status VARCHAR(20) NOT NULL DEFAULT 'quarantine', created_at VARCHAR(19) NOT NULL",
        'suppliers' => "id $pk, name VARCHAR(160) NOT NULL, email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT ''",
        'purchase_orders' => "id $pk, po_no VARCHAR(30) NOT NULL, supplier_id INT NOT NULL, location_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft', expected_date VARCHAR(10) NULL, created_at VARCHAR(19) NOT NULL",
        'po_items' => "id $pk, po_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0",
        'customers' => "id $pk, name VARCHAR(160) NOT NULL, email VARCHAR(190) NOT NULL DEFAULT '', phone VARCHAR(40) NOT NULL DEFAULT ''",
        'sales_orders' => "id $pk, so_no VARCHAR(30) NOT NULL, customer_id INT NOT NULL, location_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'new', created_at VARCHAR(19) NOT NULL, shipped_at VARCHAR(19) NULL",
        'so_items' => "id $pk, so_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, price DECIMAL(12,2) NOT NULL DEFAULT 0",
        'returns_log' => "id $pk, product_id INT NOT NULL, location_id INT NOT NULL, qty INT NOT NULL, reason VARCHAR(190) NOT NULL DEFAULT '', restocked TINYINT NOT NULL DEFAULT 0, created_at VARCHAR(19) NOT NULL",
        'transfers' => "id $pk, product_id INT NOT NULL, from_location INT NOT NULL, to_location INT NOT NULL, qty INT NOT NULL, created_at VARCHAR(19) NOT NULL",
        'cycle_counts' => "id $pk, product_id INT NOT NULL, location_id INT NOT NULL, expected INT NOT NULL, counted INT NOT NULL, created_at VARCHAR(19) NOT NULL",
        'settings' => "skey VARCHAR(60) NOT NULL PRIMARY KEY, sval TEXT NOT NULL",
    ];
    $out = [];
    foreach ($t as $name => $cols) {
        $out[] = "CREATE TABLE IF NOT EXISTS $name ($cols)$tail";
    }
    return $out;
}
