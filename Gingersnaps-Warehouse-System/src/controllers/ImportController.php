<?php
/**
 * ImportController.php
 *
 * Handles import-related HTTP requests:
 * - File upload and preview
 * - Template download
 * - Import confirmation and processing
 * - Import history retrieval
 *
 * Endpoints:
 * - POST /api/import/preview - Upload and preview file
 * - POST /api/import/process - Confirm and process import
 * - GET /api/import/template - Download CSV template
 * - GET /api/import/history - Get import history
 */

class ImportController {
    private $pdo;
    private $userId;
    private $userRole;

    public function __construct($pdo, $userId, $userRole = 'employee') {
        $this->pdo = $pdo;
        $this->userId = $userId;
        $this->userRole = $userRole;
    }

    /**
     * Handle file upload and preview
     * POST /api/import/preview
     */
    public function previewImport() {
        // Authorization check
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        // Validate file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            return $this->error('File upload failed', 400);
        }

        $file = $_FILES['file'];

        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return $this->error('File too large. Maximum 5MB allowed.', 400);
        }

        // Validate MIME type
        $allowedMimes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!in_array($file['type'], $allowedMimes)) {
            return $this->error('Invalid file type. CSV or Excel files only.', 400);
        }

        // Move to temp location
        $tempFile = tempnam(sys_get_temp_dir(), 'wpms_import_');
        if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
            return $this->error('Failed to process upload', 500);
        }

        // Handle Excel files
        if (in_array($file['type'], ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
            $tempFile = $this->convertExcelToCsv($tempFile);
            if (!$tempFile) {
                return $this->error('Failed to convert Excel file to CSV', 500);
            }
        }

        // Get preview
        $importService = new ImportService($this->pdo);
        $preview = $importService->getPreview($tempFile);

        // Store temp file path in session for later processing
        $_SESSION['import_temp_file'] = $tempFile;
        $_SESSION['import_original_name'] = $file['name'];

        return $this->success($preview);
    }

    /**
     * Handle import processing
     * POST /api/import/process
     */
    public function processImport() {
        // Authorization check
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        // Validate temp file exists
        if (!isset($_SESSION['import_temp_file']) || !file_exists($_SESSION['import_temp_file'])) {
            return $this->error('No file to import. Please upload and preview first.', 400);
        }

        // Get warehouse ID from request
        $warehouseId = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : null;
        if (!$warehouseId) {
            return $this->error('Warehouse ID is required', 400);
        }

        // Validate warehouse exists and user has access
        if (!$this->validateWarehouseAccess($warehouseId)) {
            return $this->error('Invalid warehouse or access denied', 403);
        }

        // Process import
        $tempFile = $_SESSION['import_temp_file'];
        $importService = new ImportService($this->pdo);

        $result = $importService->importRecords($tempFile, $this->userId, $warehouseId);

        // Clean up temp file
        @unlink($tempFile);
        unset($_SESSION['import_temp_file']);
        unset($_SESSION['import_original_name']);

        if ($result['success']) {
            return $this->success([
                'message' => 'Import completed successfully',
                'import_id' => $result['importId'],
                'summary' => $result['summary']
            ]);
        } else {
            return $this->error($result['message'], 400, [
                'summary' => $result['summary']
            ]);
        }
    }

    /**
     * Handle template download
     * GET /api/import/template?type=picklist
     */
    public function downloadTemplate() {
        // Authorization check
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            http_response_code(403);
            die('Permission denied');
        }

        $templateType = isset($_GET['type']) ? $_GET['type'] : 'picklist';

        // Validate template type
        if (!in_array($templateType, ['picklist', 'employee'])) {
            http_response_code(400);
            die('Invalid template type');
        }

        $generator = new ImportTemplateGenerator();
        $generator->generateAndDownload($templateType);
        exit;
    }

    /**
     * Get import history
     * GET /api/import/history
     */
    public function getImportHistory() {
        // Authorization check
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 20;

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    i.id, i.import_type, i.file_name, i.total_records,
                    i.successful_records, i.failed_records, i.status,
                    i.import_started_at, i.import_completed_at,
                    u.full_name as created_by_name
                FROM imports i
                LEFT JOIN users u ON i.created_by = u.id
                ORDER BY i.created_at DESC
                LIMIT ?
            ");

            $stmt->execute([$limit]);
            $imports = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return $this->success([
                'count' => count($imports),
                'imports' => $imports
            ]);

        } catch (\Exception $e) {
            error_log('Import history error: ' . $e->getMessage());
            return $this->error('Failed to retrieve import history', 500);
        }
    }

    /**
     * Get import details (for viewing specific import)
     * GET /api/import/:id
     */
    public function getImportDetails($importId) {
        // Authorization check
        if (!in_array($this->userRole, ['admin', 'supervisor'])) {
            return $this->error('Permission denied', 403);
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    id, import_type, file_name, total_records,
                    successful_records, failed_records, status,
                    error_summary, import_started_at, import_completed_at,
                    created_at
                FROM imports
                WHERE id = ?
            ");

            $stmt->execute([$importId]);
            $import = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$import) {
                return $this->error('Import not found', 404);
            }

            // Get import details (first 50 errors)
            $detailStmt = $this->pdo->prepare("
                SELECT
                    source_row_number, status, error_message, target_table
                FROM import_details
                WHERE import_id = ?
                ORDER BY source_row_number ASC
                LIMIT 50
            ");

            $detailStmt->execute([$importId]);
            $import['details'] = $detailStmt->fetchAll(\PDO::FETCH_ASSOC);

            return $this->success($import);

        } catch (\Exception $e) {
            error_log('Import details error: ' . $e->getMessage());
            return $this->error('Failed to retrieve import details', 500);
        }
    }

    /**
     * Convert Excel to CSV (if PhpSpreadsheet available)
     *
     * @param string $excelFile Path to Excel file
     * @return string|false Path to converted CSV or false if conversion failed
     */
    private function convertExcelToCsv($excelFile) {
        // Check if PhpSpreadsheet is available
        if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
            // PhpSpreadsheet not available, return false
            return false;
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelFile);
            $sheet = $spreadsheet->getActiveSheet();

            // Convert to CSV
            $csvFile = tempnam(sys_get_temp_dir(), 'wpms_csv_');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
            $writer->save($csvFile);

            // Clean up Excel file
            @unlink($excelFile);

            return $csvFile;

        } catch (\Exception $e) {
            error_log('Excel conversion error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate warehouse access
     *
     * @param int $warehouseId Warehouse ID
     * @return bool True if user can access warehouse
     */
    private function validateWarehouseAccess($warehouseId) {
        try {
            // Admin can access all warehouses
            if ($this->userRole === 'admin') {
                return true;
            }

            // Supervisor: verify warehouse exists
            $stmt = $this->pdo->prepare("SELECT id FROM warehouses WHERE id = ? AND is_active = 1");
            $stmt->execute([$warehouseId]);

            return $stmt->rowCount() > 0;

        } catch (\Exception $e) {
            error_log('Warehouse validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Return success response
     *
     * @param array $data Response data
     * @return array JSON response
     */
    private function success($data) {
        return [
            'success' => true,
            'code' => 200,
            'data' => $data
        ];
    }

    /**
     * Return error response
     *
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array $extra Extra data to include
     * @return array JSON response
     */
    private function error($message, $code = 400, $extra = []) {
        return array_merge([
            'success' => false,
            'code' => $code,
            'message' => $message
        ], $extra);
    }
}
?>
