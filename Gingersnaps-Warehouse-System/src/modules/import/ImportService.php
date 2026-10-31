<?php
/**
 * ImportService.php
 *
 * Handles CSV/Excel file imports for picking and packing tasks.
 * Features:
 * - CSV parsing with header validation
 * - Field validation against business rules
 * - Duplicate detection (by order code + employee + date)
 * - Transaction-based batch import
 * - Detailed error reporting
 * - Import history tracking
 *
 * Usage:
 *  $service = new ImportService($pdo);
 *  $preview = $service->getPreview($tempFile);
 *  $result = $service->importRecords($tempFile, $userId);
 */

class ImportService {
    private $pdo;
    private $errors = [];
    private $warnings = [];
    private $importId = null;

    // CSV Header mapping (from Unicommerce)
    private $requiredHeaders = [
        'Picklist_Code' => 'picklist_code',
        'Sale_Order_Code' => 'order_number',
        'Item_SkuCode' => 'sku_code',
        'Shelf_Code' => 'shelf_code',
        'Batch_Code' => 'batch_code',
        'Picklist_Status' => 'status'
    ];

    private $optionalHeaders = [
        'Item_Type_Name' => 'product_description',
        'Shipping_Package_Code' => 'package_code',
        'EAN' => 'ean',
        'Picked_By' => 'assigned_employee'
    ];

    // Validation rules
    private $validationRules = [
        'picklist_code' => [
            'required' => true,
            'pattern' => '/^[A-Z0-9\-]{3,50}$/',
            'message' => 'Invalid picklist code. Use alphanumeric and hyphens only (3-50 chars)'
        ],
        'order_number' => [
            'required' => true,
            'pattern' => '/^[0-9]{5,20}$/',
            'message' => 'Order number must be numeric (5-20 digits)'
        ],
        'sku_code' => [
            'required' => true,
            'maxLength' => 100,
            'pattern' => '/^[A-Z0-9\-_\.]{1,100}$/i',
            'message' => 'Invalid SKU code (max 100 alphanumeric chars)'
        ],
        'quantity_ordered' => [
            'required' => true,
            'pattern' => '/^[0-9]{1,5}$/',
            'message' => 'Quantity must be positive integer (1-10000)'
        ],
        'employee_id' => [
            'required' => true,
            'pattern' => '/^[0-9]{1,10}$/',
            'message' => 'Invalid employee ID'
        ],
        'warehouse_id' => [
            'required' => true,
            'pattern' => '/^[0-9]{1,10}$/',
            'message' => 'Invalid warehouse ID'
        ]
    ];

    // Status values allowed from Unicommerce
    private $validStatuses = ['IN_PROGRESS', 'PENDING', 'COMPLETED', 'CANCELLED'];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Parse CSV file and return preview (first 10 rows)
     *
     * @param string $filePath Temporary file path
     * @param int $limit Number of rows to preview
     * @return array ['success' => bool, 'data' => array, 'errors' => array, 'headers' => array]
     */
    public function getPreview($filePath, $limit = 10) {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'errors' => ['File not found'],
                'data' => [],
                'headers' => []
            ];
        }

        try {
            $handle = fopen($filePath, 'r');
            if (!$handle) {
                return [
                    'success' => false,
                    'errors' => ['Cannot open file for reading'],
                    'data' => [],
                    'headers' => []
                ];
            }

            // Read header row
            $headers = fgetcsv($handle, 1000, ',');
            if (!$headers) {
                fclose($handle);
                return [
                    'success' => false,
                    'errors' => ['Invalid CSV format - cannot read headers'],
                    'data' => [],
                    'headers' => []
                ];
            }

            // Validate headers
            $headerValidation = $this->validateHeaders($headers);
            if (!$headerValidation['valid']) {
                fclose($handle);
                return [
                    'success' => false,
                    'errors' => $headerValidation['errors'],
                    'data' => [],
                    'headers' => $headers
                ];
            }

            // Read preview rows
            $previewData = [];
            $rowNumber = 2; // Start at row 2 (after headers)

            while (($row = fgetcsv($handle, 1000, ',')) !== false && count($previewData) < $limit) {
                if (empty(array_filter($row))) {
                    // Skip empty rows
                    $rowNumber++;
                    continue;
                }

                $mappedRow = $this->mapRowToFields($row, $headers);
                $previewData[] = [
                    'row_number' => $rowNumber,
                    'data' => $mappedRow
                ];
                $rowNumber++;
            }

            fclose($handle);

            return [
                'success' => true,
                'headers' => $headers,
                'mapped_headers' => $headerValidation['mapped_headers'],
                'data' => $previewData,
                'total_rows' => $rowNumber - 2,
                'errors' => []
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'errors' => ['Error reading file: ' . $e->getMessage()],
                'data' => [],
                'headers' => []
            ];
        }
    }

    /**
     * Import records from CSV file
     *
     * @param string $filePath Temporary file path
     * @param int $userId User performing import
     * @param int $warehouseId Warehouse to assign records to
     * @return array ['success' => bool, 'importId' => int, 'summary' => array, 'errors' => array]
     */
    public function importRecords($filePath, $userId, $warehouseId) {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'File not found',
                'importId' => null,
                'summary' => []
            ];
        }

        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Create import record
            $this->importId = $this->createImportRecord($filePath, $userId);

            // Parse and validate
            $result = $this->parseAndValidate($filePath, $warehouseId);

            if (!$result['valid']) {
                $this->pdo->rollBack();
                return [
                    'success' => false,
                    'message' => 'Validation failed',
                    'importId' => null,
                    'summary' => [
                        'total_records' => $result['total'],
                        'successful_records' => 0,
                        'failed_records' => $result['total'],
                        'errors' => array_slice($result['errors'], 0, 50) // Limit to first 50
                    ]
                ];
            }

            // Insert validated records
            $insertResult = $this->insertRecords($result['records'], $userId);

            // Update import record with summary
            $this->updateImportRecord([
                'successful_records' => $insertResult['successful'],
                'failed_records' => $insertResult['failed'],
                'status' => 'completed'
            ]);

            $this->pdo->commit();

            return [
                'success' => true,
                'message' => 'Import completed successfully',
                'importId' => $this->importId,
                'summary' => [
                    'total_records' => count($result['records']),
                    'successful_records' => $insertResult['successful'],
                    'failed_records' => $insertResult['failed'],
                    'warnings' => array_slice($this->warnings, 0, 10)
                ]
            ];

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log('Import error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'importId' => $this->importId,
                'summary' => []
            ];
        }
    }

    /**
     * Parse CSV and validate all records
     *
     * @param string $filePath File path
     * @param int $warehouseId Warehouse ID
     * @return array ['valid' => bool, 'records' => array, 'errors' => array, 'total' => int]
     */
    private function parseAndValidate($filePath, $warehouseId) {
        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 1000, ',');

        // Validate headers
        $headerValidation = $this->validateHeaders($headers);
        if (!$headerValidation['valid']) {
            fclose($handle);
            return [
                'valid' => false,
                'records' => [],
                'errors' => $headerValidation['errors'],
                'total' => 0
            ];
        }

        $records = [];
        $rowNumber = 2;
        $errors = [];

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if (empty(array_filter($row))) {
                $rowNumber++;
                continue;
            }

            $mapped = $this->mapRowToFields($row, $headers);
            $mapped['warehouse_id'] = $warehouseId;

            // Validate row
            $validation = $this->validateRecord($mapped, $rowNumber);

            if ($validation['valid']) {
                // Check for duplicates
                $duplicate = $this->checkDuplicate($mapped);
                if ($duplicate) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => 'Duplicate record detected (picklist code: ' . $mapped['picklist_code'] . ')',
                        'type' => 'duplicate'
                    ];
                } else {
                    $records[] = $mapped;
                }
            } else {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $validation['errors'],
                    'type' => 'validation'
                ];
            }

            $rowNumber++;
        }

        fclose($handle);

        return [
            'valid' => empty($errors),
            'records' => $records,
            'errors' => $errors,
            'total' => $rowNumber - 2
        ];
    }

    /**
     * Validate CSV headers
     *
     * @param array $headers CSV headers
     * @return array ['valid' => bool, 'errors' => array, 'mapped_headers' => array]
     */
    private function validateHeaders($headers) {
        $errors = [];
        $mapped = [];
        $headerTrim = array_map('trim', $headers);

        // Check required headers
        foreach ($this->requiredHeaders as $csvHeader => $dbField) {
            if (!in_array($csvHeader, $headerTrim)) {
                $errors[] = "Missing required header: $csvHeader";
            } else {
                $mapped[$csvHeader] = $dbField;
            }
        }

        // Check optional headers
        foreach ($this->optionalHeaders as $csvHeader => $dbField) {
            if (in_array($csvHeader, $headerTrim)) {
                $mapped[$csvHeader] = $dbField;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'mapped_headers' => $mapped
        ];
    }

    /**
     * Map CSV row to database fields
     *
     * @param array $row CSV row
     * @param array $headers CSV headers
     * @return array Mapped fields
     */
    private function mapRowToFields($row, $headers) {
        $mapped = [];
        $headerTrim = array_map('trim', $headers);

        foreach ($headerTrim as $index => $header) {
            $value = isset($row[$index]) ? trim($row[$index]) : '';

            if (isset($this->requiredHeaders[$header])) {
                $dbField = $this->requiredHeaders[$header];
                $mapped[$dbField] = $value;
            } elseif (isset($this->optionalHeaders[$header])) {
                $dbField = $this->optionalHeaders[$header];
                $mapped[$dbField] = $value;
            }
        }

        // Default values
        $mapped['quantity_ordered'] = $mapped['quantity_ordered'] ?? 1;
        $mapped['status'] = 'created'; // Always start with created

        return $mapped;
    }

    /**
     * Validate single record against rules
     *
     * @param array $record Record to validate
     * @param int $rowNumber Row number for error reporting
     * @return array ['valid' => bool, 'errors' => array]
     */
    private function validateRecord($record, $rowNumber) {
        $errors = [];

        // Required field checks
        if (empty($record['picklist_code'])) {
            $errors[] = 'Picklist code is required';
        } elseif (!preg_match($this->validationRules['picklist_code']['pattern'], $record['picklist_code'])) {
            $errors[] = $this->validationRules['picklist_code']['message'];
        }

        if (empty($record['order_number'])) {
            $errors[] = 'Order number is required';
        } elseif (!preg_match($this->validationRules['order_number']['pattern'], $record['order_number'])) {
            $errors[] = $this->validationRules['order_number']['message'];
        }

        if (empty($record['sku_code'])) {
            $errors[] = 'SKU code is required';
        } elseif (!preg_match($this->validationRules['sku_code']['pattern'], $record['sku_code'])) {
            $errors[] = $this->validationRules['sku_code']['message'];
        }

        // Quantity validation
        $qty = $record['quantity_ordered'] ?? 1;
        if (!is_numeric($qty) || $qty < 1 || $qty > 10000) {
            $errors[] = 'Quantity must be between 1 and 10000';
        }

        // Status validation if provided
        if (!empty($record['status']) && !in_array($record['status'], $this->validStatuses)) {
            $errors[] = 'Invalid status: ' . $record['status'];
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check for duplicate records
     * Duplicates = same picklist_code OR (same order_number + same employee + same date)
     *
     * @param array $record Record to check
     * @return bool True if duplicate found
     */
    private function checkDuplicate($record) {
        try {
            // Check by picklist code (most reliable)
            $stmt = $this->pdo->prepare("
                SELECT id FROM picking_tasks
                WHERE picklist_code = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$record['picklist_code']]);

            if ($stmt->rowCount() > 0) {
                return true;
            }

            // Check by order + date (within last 24 hours)
            $stmt = $this->pdo->prepare("
                SELECT id FROM picking_tasks
                WHERE order_number = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                LIMIT 1
            ");
            $stmt->execute([$record['order_number']]);

            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            error_log('Duplicate check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Insert validated records into database
     *
     * @param array $records Records to insert
     * @param int $userId User performing import
     * @return array ['successful' => int, 'failed' => int]
     */
    private function insertRecords($records, $userId) {
        $successful = 0;
        $failed = 0;

        $stmt = $this->pdo->prepare("
            INSERT INTO picking_tasks (
                picklist_code, employee_id, order_number, sku_code,
                product_description, quantity_ordered, shelf_code, batch_code,
                status, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, NOW()
            )
        ");

        foreach ($records as $record) {
            try {
                // Get employee_id from emp_id
                $empStmt = $this->pdo->prepare("
                    SELECT id FROM employees
                    WHERE emp_id = ? AND is_active = 1
                    LIMIT 1
                ");
                $empStmt->execute([$record['assigned_employee'] ?? '']);
                $empResult = $empStmt->fetch();

                $employeeId = $empResult ? $empResult['id'] : null;

                $stmt->execute([
                    $record['picklist_code'],
                    $employeeId,
                    $record['order_number'],
                    $record['sku_code'],
                    $record['product_description'] ?? '',
                    $record['quantity_ordered'] ?? 1,
                    $record['shelf_code'] ?? '',
                    $record['batch_code'] ?? '',
                    'pending',
                    $userId
                ]);

                $successful++;

            } catch (\Exception $e) {
                error_log('Insert error for picklist ' . $record['picklist_code'] . ': ' . $e->getMessage());
                $failed++;
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed
        ];
    }

    /**
     * Create import record in database
     *
     * @param string $filePath File path
     * @param int $userId User performing import
     * @return int Import ID
     */
    private function createImportRecord($filePath, $userId) {
        $fileName = basename($filePath);
        $fileSize = filesize($filePath);

        $stmt = $this->pdo->prepare("
            INSERT INTO imports (
                import_type, file_name, file_size, status,
                import_started_at, created_by, created_at
            ) VALUES (
                'PICKLIST_IMPORT', ?, ?, 'processing', NOW(), ?, NOW()
            )
        ");

        $stmt->execute([$fileName, $fileSize, $userId]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Update import record with results
     *
     * @param array $data Data to update
     * @return bool Success
     */
    private function updateImportRecord($data) {
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $this->importId;

        $sql = "UPDATE imports SET " . implode(', ', $updates) . ", import_completed_at = NOW() WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    /**
     * Get import history
     *
     * @param int $limit Records to return
     * @return array Import records
     */
    public function getImportHistory($limit = 20) {
        $stmt = $this->pdo->prepare("
            SELECT
                id, import_type, file_name, total_records,
                successful_records, failed_records, status,
                import_started_at, import_completed_at,
                created_by, created_at
            FROM imports
            ORDER BY created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
?>
