<?php
/**
 * CSVExporter.php
 *
 * Exports report data to CSV format with proper formatting and metadata.
 * Handles large datasets efficiently with streaming support.
 *
 * Features:
 * - Proper CSV field escaping and formatting
 * - Report metadata (generated date, filters, column descriptions)
 * - Streaming output for large datasets (100+K rows)
 * - Customizable column selection and ordering
 * - Support for all WPMS report types
 * - File download or string return options
 *
 * Usage:
 *  $exporter = new CSVExporter();
 *  $csv = $exporter->exportToString($reportData);
 *  // or
 *  $exporter->downloadCSV($reportData, 'picking_performance_report.csv');
 *
 * @author WPMS Development Team
 * @version 1.0.0
 * @date October 24, 2026
 */

class CSVExporter {

    // CSV configuration constants
    const CSV_DELIMITER = ',';
    const CSV_ENCLOSURE = '"';
    const CSV_ESCAPE = '"';
    const LINE_ENDING = "\r\n";

    // Report type metadata
    private $reportMetadata = [
        'picking_performance' => [
            'title' => 'Picking Performance Report',
            'description' => 'Employee-level picking metrics and productivity data'
        ],
        'packing_performance' => [
            'title' => 'Packing Performance Report',
            'description' => 'Employee-level packing metrics and quality data'
        ],
        'warehouse_productivity' => [
            'title' => 'Warehouse Productivity Report',
            'description' => 'Warehouse and team-level productivity aggregates'
        ],
        'executive_summary' => [
            'title' => 'Executive Summary Report',
            'description' => 'High-level KPIs and key performance indicators'
        ]
    ];

    // Column descriptions for metadata
    private $columnDescriptions = [
        'employee_id' => 'Unique employee identifier',
        'employee_name' => 'Full name of employee',
        'employee_code' => 'External employee ID/code',
        'department' => 'Department assignment',
        'warehouse' => 'Warehouse location',
        'total_picks' => 'Total picking tasks assigned',
        'completed_picks' => 'Completed picking tasks',
        'pending_picks' => 'Pending picking tasks',
        'avg_pick_time_minutes' => 'Average time per pick (minutes)',
        'error_count' => 'Total errors recorded',
        'error_rate_percent' => 'Percentage of tasks with errors',
        'rework_count' => 'Tasks requiring rework',
        'rework_percent' => 'Percentage of tasks requiring rework',
        'completion_rate_percent' => 'Percentage of tasks completed',
        'tasks_per_hour' => 'Average tasks completed per hour',
        'total_packs' => 'Total packing tasks assigned',
        'completed_packs' => 'Completed packing tasks',
        'quality_error_rate_percent' => 'Percentage of packs with quality issues',
        'damage_count' => 'Number of damaged packages',
        'discrepancy_count' => 'Number of discrepancies found',
        'warehouse_name' => 'Name of warehouse facility',
        'active_employees' => 'Number of active employees',
        'total_tasks_assigned' => 'Total tasks in period',
        'total_tasks_completed' => 'Tasks successfully completed',
        'overall_completion_rate_percent' => 'Overall completion percentage'
    ];

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize if needed
    }

    /**
     * Export report to CSV string
     *
     * @param array $reportData Report data from ReportGenerator
     * @param array $options Options:
     *   - include_metadata: bool (default true) - include report metadata
     *   - columns: array - specific columns to include (default: all)
     *   - skip_empty_columns: bool (default false) - skip columns with no data
     * @return string CSV formatted string
     * @throws Exception If report data is invalid
     */
    public function exportToString($reportData, $options = []) {
        try {
            $this->validateReportData($reportData);

            $output = '';
            $includeMetadata = $options['include_metadata'] ?? true;

            // Add metadata section if requested
            if ($includeMetadata) {
                $output .= $this->generateMetadata($reportData);
                $output .= self::LINE_ENDING . self::LINE_ENDING;
            }

            // Get report data rows
            $rows = $reportData['data'] ?? [];

            if (empty($rows)) {
                return $output . "No data available for this report." . self::LINE_ENDING;
            }

            // Determine columns to export
            $columns = $this->getExportColumns($rows, $options);

            if (empty($columns)) {
                throw new \Exception('No valid columns found in report data');
            }

            // Write header row
            $output .= $this->escapeCSVLine(array_keys($columns)) . self::LINE_ENDING;

            // Write data rows
            foreach ($rows as $row) {
                $csvRow = [];
                foreach ($columns as $column => $description) {
                    $csvRow[] = $this->formatCSVValue($row[$column] ?? '');
                }
                $output .= implode(self::CSV_DELIMITER, $csvRow) . self::LINE_ENDING;
            }

            return $output;

        } catch (\Exception $e) {
            error_log('CSV Export Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export report to CSV file and trigger download
     *
     * @param array $reportData Report data from ReportGenerator
     * @param string $filename Filename for download
     * @param array $options Export options (see exportToString)
     * @return void
     * @throws Exception If export fails
     */
    public function downloadCSV($reportData, $filename = null, $options = []) {
        try {
            if (!$filename) {
                $reportType = $reportData['report_type'] ?? 'report';
                $filename = $reportType . '_' . date('Y-m-d_His') . '.csv';
            }

            // Generate CSV content
            $csv = $this->exportToString($reportData, $options);

            // Set headers for download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename($filename) . '"');
            header('Content-Length: ' . strlen($csv));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');

            // Output CSV
            echo $csv;
            exit;

        } catch (\Exception $e) {
            error_log('CSV Download Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stream large report to file for export
     *
     * Useful for very large datasets (100K+ rows) to avoid memory issues.
     *
     * @param array $reportData Report data
     * @param string $outputPath Full path to output file
     * @param array $options Export options
     * @return array Result with file info
     * @throws Exception If streaming fails
     */
    public function streamToFile($reportData, $outputPath, $options = []) {
        try {
            $this->validateReportData($reportData);

            $handle = fopen($outputPath, 'w');
            if (!$handle) {
                throw new \Exception('Unable to open file for writing: ' . $outputPath);
            }

            $includeMetadata = $options['include_metadata'] ?? true;
            $bytesWritten = 0;
            $rowsWritten = 0;

            try {
                // Write metadata
                if ($includeMetadata) {
                    $metadata = $this->generateMetadata($reportData);
                    $bytesWritten += fwrite($handle, $metadata);
                    $bytesWritten += fwrite($handle, self::LINE_ENDING . self::LINE_ENDING);
                }

                // Get rows and columns
                $rows = $reportData['data'] ?? [];
                if (empty($rows)) {
                    fwrite($handle, "No data available for this report." . self::LINE_ENDING);
                    fclose($handle);
                    return [
                        'success' => true,
                        'file_path' => $outputPath,
                        'file_size' => filesize($outputPath),
                        'rows_written' => 0,
                        'bytes_written' => filesize($outputPath)
                    ];
                }

                $columns = $this->getExportColumns($rows, $options);

                // Write header
                $headerLine = $this->escapeCSVLine(array_keys($columns)) . self::LINE_ENDING;
                $bytesWritten += fwrite($handle, $headerLine);

                // Stream rows
                foreach ($rows as $row) {
                    $csvRow = [];
                    foreach ($columns as $column => $description) {
                        $csvRow[] = $this->formatCSVValue($row[$column] ?? '');
                    }
                    $line = implode(self::CSV_DELIMITER, $csvRow) . self::LINE_ENDING;
                    $bytesWritten += fwrite($handle, $line);
                    $rowsWritten++;

                    // Flush every 100 rows to manage memory
                    if ($rowsWritten % 100 === 0) {
                        fflush($handle);
                    }
                }

                fclose($handle);

                return [
                    'success' => true,
                    'file_path' => $outputPath,
                    'file_size' => filesize($outputPath),
                    'rows_written' => $rowsWritten,
                    'bytes_written' => $bytesWritten
                ];

            } catch (\Exception $e) {
                fclose($handle);
                if (file_exists($outputPath)) {
                    unlink($outputPath);
                }
                throw $e;
            }

        } catch (\Exception $e) {
            error_log('CSV Stream Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Export report with custom column mapping
     *
     * Allows reordering and renaming columns for output.
     *
     * @param array $reportData Report data
     * @param array $columnMap Mapping: ['csv_header' => 'data_field', ...]
     * @param bool $includeMetadata Include metadata
     * @return string CSV formatted string
     */
    public function exportWithCustomColumns($reportData, $columnMap, $includeMetadata = true) {
        try {
            $this->validateReportData($reportData);

            $output = '';

            if ($includeMetadata) {
                $output .= $this->generateMetadata($reportData);
                $output .= self::LINE_ENDING . self::LINE_ENDING;
            }

            $rows = $reportData['data'] ?? [];

            if (empty($rows)) {
                return $output . "No data available." . self::LINE_ENDING;
            }

            // Write custom header
            $output .= $this->escapeCSVLine(array_keys($columnMap)) . self::LINE_ENDING;

            // Write rows with mapped columns
            foreach ($rows as $row) {
                $csvRow = [];
                foreach ($columnMap as $csvHeader => $dataField) {
                    $csvRow[] = $this->formatCSVValue($row[$dataField] ?? '');
                }
                $output .= implode(self::CSV_DELIMITER, $csvRow) . self::LINE_ENDING;
            }

            return $output;

        } catch (\Exception $e) {
            error_log('Custom Column Export Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get summary statistics for report
     *
     * @param array $reportData Report data
     * @return array Summary with row count, columns, filters
     */
    public function getReportSummary($reportData) {
        try {
            $this->validateReportData($reportData);

            $rows = $reportData['data'] ?? [];
            $columns = !empty($rows) ? array_keys($rows[0]) : [];

            return [
                'report_type' => $reportData['report_type'] ?? 'unknown',
                'generated_at' => $reportData['generated_at'] ?? null,
                'date_range' => $reportData['date_range'] ?? null,
                'filters' => $reportData['filters_applied'] ?? [],
                'total_rows' => count($rows),
                'total_columns' => count($columns),
                'columns' => $columns,
                'estimated_file_size_kb' => round(strlen(json_encode($rows)) / 1024, 2)
            ];

        } catch (\Exception $e) {
            error_log('Summary Error: ' . $e->getMessage());
            return [];
        }
    }

    // ========== PRIVATE HELPER METHODS ==========

    /**
     * Validate report data structure
     */
    private function validateReportData($reportData) {
        if (!is_array($reportData)) {
            throw new \Exception('Report data must be an array');
        }

        if (empty($reportData)) {
            throw new \Exception('Report data is empty');
        }

        if (!isset($reportData['report_type'])) {
            throw new \Exception('Report data must include report_type field');
        }
    }

    /**
     * Format a value for CSV output
     *
     * Handles:
     * - Null values → empty string
     * - Arrays → JSON string
     * - Numbers → as-is
     * - Strings → trimmed, ready for escaping
     */
    private function formatCSVValue($value) {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            // For nested arrays (like error_types), convert to readable format
            if (empty($value)) {
                return '';
            }
            return json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_numeric($value)) {
            return (string)$value;
        }

        return (string)trim($value);
    }

    /**
     * Escape and format a CSV line
     *
     * Properly escapes fields and returns as CSV string.
     *
     * @param array $fields Array of field values
     * @return string CSV formatted line
     */
    private function escapeCSVLine($fields) {
        $escapedFields = [];

        foreach ($fields as $field) {
            $field = (string)$field;

            // Check if field needs quoting
            if (strpos($field, self::CSV_DELIMITER) !== false ||
                strpos($field, self::CSV_ENCLOSURE) !== false ||
                strpos($field, "\n") !== false ||
                strpos($field, "\r") !== false) {

                // Escape quotes within field
                $field = str_replace(self::CSV_ENCLOSURE, self::CSV_ESCAPE . self::CSV_ENCLOSURE, $field);

                // Wrap in quotes
                $field = self::CSV_ENCLOSURE . $field . self::CSV_ENCLOSURE;
            }

            $escapedFields[] = $field;
        }

        return implode(self::CSV_DELIMITER, $escapedFields);
    }

    /**
     * Generate metadata section for CSV
     *
     * @param array $reportData Report data
     * @return string Metadata lines
     */
    private function generateMetadata($reportData) {
        $lines = [];

        // Report title and description
        $reportType = $reportData['report_type'] ?? 'unknown';
        $metadata = $this->reportMetadata[$reportType] ?? ['title' => ucwords(str_replace('_', ' ', $reportType))];

        $lines[] = 'Report: ' . $metadata['title'];
        if (isset($metadata['description'])) {
            $lines[] = 'Description: ' . $metadata['description'];
        }

        // Generated date/time
        if (isset($reportData['generated_at'])) {
            $lines[] = 'Generated: ' . $reportData['generated_at'];
        }

        // Date range
        if (isset($reportData['date_range'])) {
            $dr = $reportData['date_range'];
            $lines[] = 'Period: ' . ($dr['start_date'] ?? '') . ' to ' . ($dr['end_date'] ?? '');
            if (isset($dr['business_days'])) {
                $lines[] = 'Business Days: ' . $dr['business_days'];
            }
        }

        // Filters applied
        if (isset($reportData['filters_applied']) && !empty($reportData['filters_applied'])) {
            $filters = $reportData['filters_applied'];
            $filterStrings = [];

            foreach ($filters as $key => $value) {
                $filterStrings[] = ucwords(str_replace('_', ' ', $key)) . ': ' . $value;
            }

            if (!empty($filterStrings)) {
                $lines[] = 'Filters: ' . implode(', ', $filterStrings);
            }
        }

        // Record count
        $rows = $reportData['data'] ?? [];
        $lines[] = 'Records: ' . count($rows);

        return implode(self::LINE_ENDING, $lines);
    }

    /**
     * Determine which columns to export
     *
     * @param array $rows Data rows
     * @param array $options Export options
     * @return array Column mapping
     */
    private function getExportColumns($rows, $options = []) {
        if (empty($rows)) {
            return [];
        }

        $firstRow = $rows[0];
        $availableColumns = array_keys($firstRow);

        // If specific columns requested, use those
        if (isset($options['columns']) && is_array($options['columns'])) {
            $requestedColumns = $options['columns'];
            $columns = [];

            foreach ($requestedColumns as $col) {
                if (in_array($col, $availableColumns)) {
                    $columns[$col] = $this->columnDescriptions[$col] ?? '';
                }
            }

            return $columns;
        }

        // Default: all columns except internal fields
        $skipColumns = ['record_count', 'warehouse_count', 'filters_applied', 'date_range'];
        $columns = [];

        foreach ($availableColumns as $col) {
            if (!in_array($col, $skipColumns)) {
                $columns[$col] = $this->columnDescriptions[$col] ?? '';
            }
        }

        return $columns;
    }

    /**
     * Sanitize filename for download
     *
     * @param string $filename Original filename
     * @return string Safe filename
     */
    private function sanitizeFilename($filename) {
        // Remove path components
        $filename = basename($filename);

        // Remove special characters except underscore and dash
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);

        // Limit length
        if (strlen($filename) > 200) {
            $filename = substr($filename, 0, 200);
        }

        return $filename;
    }
}
?>
