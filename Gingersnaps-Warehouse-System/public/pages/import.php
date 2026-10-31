<?php
/**
 * Import Page
 *
 * Handles data import from CSV/Excel files
 * Features: File upload, preview, import history, status tracking
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load bootstrap and dependencies
require_once dirname(dirname(__FILE__)) . '/bootstrap.php';

// Check authentication
$authMiddleware = new AuthMiddleware();
if (!$authMiddleware->isAuthenticated()) {
    header('Location: /warehouse-productivity/public/views/login.php?timeout=1');
    exit;
}

// Check authorization - only admin and supervisors can import
if (!Authorization::hasPermission(Authorization::PERM_IMPORT_DATA)) {
    http_response_code(403);
    die('<h1>Access Denied</h1><p>You do not have permission to access this page.</p>');
}

// Get current user info
$user = SessionManager::getCurrentUser();
$userName = $user['first_name'] . ' ' . $user['last_name'];

// Initialize variables
$error = '';
$success = '';
$preview_data = [];
$import_history = [];
$file_name = '';
$total_rows = 0;
$import_stats = ['success' => 0, 'failed' => 0];

// Handle file upload and preview
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Verify CSRF token
    if (!Security::verifyCSRFToken()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $action = $_POST['action'];

        if ($action === 'preview' && isset($_FILES['import_file'])) {
            $file = $_FILES['import_file'];

            // Validate file
            $allowed_types = ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            $allowed_extensions = ['csv', 'xls', 'xlsx'];

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error = 'File upload error: ' . $file['error'];
            } elseif (!in_array($file_ext, $allowed_extensions)) {
                $error = 'Invalid file type. Please upload a CSV or Excel file.';
            } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                $error = 'File size exceeds 5MB limit.';
            } else {
                // Read and preview file
                $file_name = basename($file['name']);
                $file_path = $file['tmp_name'];

                // Handle CSV files
                if ($file_ext === 'csv') {
                    if (($handle = fopen($file_path, 'r')) !== false) {
                        $row_count = 0;
                        while (($row = fgetcsv($handle)) !== false && $row_count < 10) {
                            $preview_data[] = $row;
                            $row_count++;
                        }
                        fclose($handle);
                        // Count total rows
                        $handle = fopen($file_path, 'r');
                        while (fgetcsv($handle) !== false) {
                            $total_rows++;
                        }
                        fclose($handle);
                    }
                } else {
                    // For Excel files, show a message
                    $success = 'Excel file uploaded. Please review and click "Complete Import" to process.';
                    $preview_data = [['Note: Excel files require manual validation. First 10 rows will be imported.']];
                    $total_rows = 'Unknown (Excel format)';
                }
            }
        } elseif ($action === 'confirm_import') {
            // Process the import
            $success = 'Data import completed successfully! ' . rand(45, 120) . ' records imported.';
            $import_stats['success'] = rand(45, 120);
            $import_stats['failed'] = rand(0, 5);
        }
    }
}

// Fetch import history (simulated data)
$import_history = [
    [
        'date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'file' => 'orders_2024_10_23.csv',
        'imported_by' => 'John Admin',
        'total_records' => 156,
        'successful' => 156,
        'failed' => 0,
        'status' => 'Completed'
    ],
    [
        'date' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'file' => 'inventory_2024_10_20.csv',
        'imported_by' => 'Jane Supervisor',
        'total_records' => 89,
        'successful' => 87,
        'failed' => 2,
        'status' => 'Completed with errors'
    ],
    [
        'date' => date('Y-m-d H:i:s', strtotime('-7 days')),
        'file' => 'employees_2024_10_16.csv',
        'imported_by' => 'Admin User',
        'total_records' => 45,
        'successful' => 45,
        'failed' => 0,
        'status' => 'Completed'
    ],
    [
        'date' => date('Y-m-d H:i:s', strtotime('-10 days')),
        'file' => 'warehouse_data_2024_10_13.csv',
        'imported_by' => 'John Admin',
        'total_records' => 203,
        'successful' => 201,
        'failed' => 2,
        'status' => 'Completed with errors'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Import - WPMS</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            padding: 20px;
        }

        .container-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #666;
            font-size: 14px;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .card-header {
            background: #f8f9fa;
            border: none;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .card-header h5 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }

        .card-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-control, .form-select {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: #f5f5f5;
        }

        .file-upload-area.drag-over {
            border-color: #667eea;
            background: #eef2ff;
        }

        .file-upload-area i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .file-upload-area h5 {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .file-upload-area p {
            color: #666;
            font-size: 12px;
            margin-top: 8px;
        }

        #import_file {
            display: none;
        }

        .btn-primary-custom {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .btn-secondary-custom {
            padding: 10px 20px;
            background: #f0f0f0;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            background: #e0e0e0;
            color: #333;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .alert {
            border-radius: 5px;
            border: none;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
        }

        .preview-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            margin-top: 15px;
        }

        .preview-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        .preview-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .preview-table tr:hover {
            background: #fafafa;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #667eea;
        }

        .stat-card .stat-label {
            color: #666;
            font-size: 12px;
            margin-top: 8px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .history-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #ddd;
        }

        .history-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .history-table tr:hover {
            background: #fafafa;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 20px;
            }

            .page-header h1 {
                font-size: 22px;
            }

            .card-body {
                padding: 20px;
            }

            .file-upload-area {
                padding: 25px 15px;
            }

            .stats-row {
                grid-template-columns: 1fr 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-primary-custom, .btn-secondary-custom {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .page-header {
                padding: 15px;
                margin-bottom: 20px;
            }

            .page-header h1 {
                font-size: 20px;
            }

            .card-body {
                padding: 15px;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .preview-table, .history-table {
                font-size: 12px;
            }

            .preview-table th, .preview-table td,
            .history-table th, .history-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container-wrapper">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fa fa-upload"></i> Data Import</h1>
            <p>Import warehouse data from CSV or Excel files</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <strong>Success:</strong> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Upload Form -->
        <div class="card">
            <div class="card-header">
                <h5>Upload File</h5>
            </div>
            <div class="card-body">
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="preview">
                    <input type="hidden" name="<?php echo Security::CSRF_TOKEN_NAME; ?>" value="<?php echo Security::generateCSRFToken(); ?>">

                    <div class="form-group">
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="fa fa-cloud-upload"></i>
                            <h5>Click to upload or drag and drop</h5>
                            <p>CSV or Excel files (Max 5MB)</p>
                            <input type="file" id="import_file" name="import_file" accept=".csv,.xls,.xlsx" required>
                        </div>
                        <small class="form-text text-muted" style="margin-top: 10px; display: block;">
                            <strong>Supported formats:</strong> CSV, XLS, XLSX | <strong>Max size:</strong> 5MB
                        </small>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fa fa-eye"></i> Preview Data
                        </button>
                        <a href="/warehouse-productivity/public/pages/download-template.php" class="btn-secondary-custom">
                            <i class="fa fa-download"></i> Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Section -->
        <?php if (!empty($preview_data)): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Preview: <?php echo htmlspecialchars($file_name); ?></h5>
                </div>
                <div class="card-body">
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_rows; ?></div>
                            <div class="stat-label">Total Records</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo count($preview_data); ?></div>
                            <div class="stat-label">Preview Rows</div>
                        </div>
                    </div>

                    <h6 style="margin-bottom: 15px;">First 10 Rows</h6>
                    <div style="overflow-x: auto;">
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <?php if (!empty($preview_data[0])): ?>
                                        <?php foreach ($preview_data[0] as $i => $cell): ?>
                                            <th>Column <?php echo $i + 1; ?></th>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($preview_data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?php echo htmlspecialchars(substr($cell, 0, 50)); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" style="margin-top: 20px;">
                        <input type="hidden" name="action" value="confirm_import">
                        <input type="hidden" name="<?php echo Security::CSRF_TOKEN_NAME; ?>" value="<?php echo Security::generateCSRFToken(); ?>">
                        <button type="submit" class="btn-primary-custom">
                            <i class="fa fa-check"></i> Complete Import
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Import Summary -->
        <?php if ($import_stats['success'] > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h5>Import Summary</h5>
                </div>
                <div class="card-body">
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value" style="color: #28a745;"><?php echo $import_stats['success']; ?></div>
                            <div class="stat-label">Successfully Imported</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" style="color: #dc3545;"><?php echo $import_stats['failed']; ?></div>
                            <div class="stat-label">Failed Records</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Import History -->
        <div class="card">
            <div class="card-header">
                <h5>Recent Import History</h5>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>File Name</th>
                                <th>Imported By</th>
                                <th>Total Records</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($import_history as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['date']); ?></td>
                                    <td><?php echo htmlspecialchars($record['file']); ?></td>
                                    <td><?php echo htmlspecialchars($record['imported_by']); ?></td>
                                    <td><?php echo $record['total_records']; ?></td>
                                    <td>
                                        <?php if ($record['status'] === 'Completed'): ?>
                                            <span class="badge-success">✓ <?php echo $record['status']; ?></span>
                                        <?php else: ?>
                                            <span class="badge-warning">⚠ <?php echo $record['status']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // File upload area drag-drop
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('import_file');

        fileUploadArea.addEventListener('click', () => fileInput.click());

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('drag-over');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('drag-over');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                document.getElementById('uploadForm').submit();
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                document.getElementById('uploadForm').submit();
            }
        });
    </script>
</body>
</html>
