<?php
/**
 * ImportTemplateGenerator.php
 *
 * Generates downloadable CSV import templates with sample data.
 *
 * Usage:
 *  $generator = new ImportTemplateGenerator();
 *  $generator->generateAndDownload('picklist');
 */

class ImportTemplateGenerator {

    // Template definitions
    private $templates = [
        'picklist' => [
            'filename' => 'WPMS-Picklist-Import-Template.csv',
            'description' => 'Unicommerce Picklist Import',
            'headers' => [
                'Picklist_Code',
                'Sale_Order_Code',
                'Sale_Order_Item_Code',
                'Item_SkuCode',
                'Item_Type_Name',
                'Shipping_Package_Code',
                'Shelf_Code',
                'Batch_Code',
                'Picklist_Status',
                'Item_Status',
                'EAN'
            ]
        ],
        'employee' => [
            'filename' => 'WPMS-Employee-Import-Template.csv',
            'description' => 'Employee Records Import',
            'headers' => [
                'Employee_ID',
                'Full_Name',
                'Email',
                'Phone',
                'Department',
                'Warehouse',
                'Employment_Status',
                'Hire_Date'
            ]
        ]
    ];

    // Sample data
    private $sampleData = [
        'picklist' => [
            [
                'PT-SAMPLE-001',
                'SO-112191',
                'SO-112191-001',
                'SKU-ASMR-001',
                'Surveillance Camera',
                'PKG-001',
                'SHELF-A1',
                'BATCH-2026-001',
                'PENDING',
                'In Progress',
                '8901234567890'
            ],
            [
                'PT-SAMPLE-002',
                'SO-112192',
                'SO-112192-001',
                'SKU-ASMR-002',
                'NVR System',
                'PKG-002',
                'SHELF-B2',
                'BATCH-2026-002',
                'IN_PROGRESS',
                'In Progress',
                '8901234567891'
            ]
        ],
        'employee' => [
            [
                'EMP001',
                'Juan Dela Cruz',
                'juan.cruz@gingersnaps.ph',
                '555-0101',
                'Picking',
                'Main Warehouse',
                'active',
                '2025-01-15'
            ],
            [
                'EMP002',
                'Maria Santos',
                'maria.santos@gingersnaps.ph',
                '555-0102',
                'Packing',
                'Main Warehouse',
                'active',
                '2025-02-20'
            ]
        ]
    ];

    /**
     * Generate template and send as download
     *
     * @param string $templateType Template type ('picklist' or 'employee')
     * @return bool True on success
     */
    public function generateAndDownload($templateType) {
        if (!isset($this->templates[$templateType])) {
            return false;
        }

        $template = $this->templates[$templateType];

        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $template['filename']);
        header('Pragma: no-cache');
        header('Expires: 0');

        // Open output stream
        $output = fopen('php://output', 'w');

        // Write BOM for Excel compatibility
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Write headers
        fputcsv($output, $template['headers']);

        // Write sample data
        if (isset($this->sampleData[$templateType])) {
            foreach ($this->sampleData[$templateType] as $row) {
                fputcsv($output, $row);
            }
        }

        fclose($output);
        return true;
    }

    /**
     * Generate template as string (for display/testing)
     *
     * @param string $templateType Template type
     * @return string CSV content
     */
    public function generateAsString($templateType) {
        if (!isset($this->templates[$templateType])) {
            return '';
        }

        $template = $this->templates[$templateType];
        $csv = '';

        // Write headers
        $csv .= implode(',', $template['headers']) . "\n";

        // Write sample data
        if (isset($this->sampleData[$templateType])) {
            foreach ($this->sampleData[$templateType] as $row) {
                $csv .= implode(',', array_map(function($field) {
                    // Escape quotes in fields
                    if (strpos($field, ',') !== false || strpos($field, '"') !== false) {
                        return '"' . str_replace('"', '""', $field) . '"';
                    }
                    return $field;
                }, $row)) . "\n";
            }
        }

        return $csv;
    }

    /**
     * Get template information
     *
     * @param string $templateType Template type
     * @return array Template info or null
     */
    public function getTemplateInfo($templateType) {
        if (!isset($this->templates[$templateType])) {
            return null;
        }

        $template = $this->templates[$templateType];

        return [
            'type' => $templateType,
            'filename' => $template['filename'],
            'description' => $template['description'],
            'headers' => $template['headers'],
            'header_count' => count($template['headers']),
            'sample_rows' => isset($this->sampleData[$templateType]) ? count($this->sampleData[$templateType]) : 0
        ];
    }

    /**
     * List all available templates
     *
     * @return array Available templates
     */
    public function listTemplates() {
        $templates = [];

        foreach ($this->templates as $type => $info) {
            $templates[] = $this->getTemplateInfo($type);
        }

        return $templates;
    }

    /**
     * Get instructions for using template
     *
     * @param string $templateType Template type
     * @return string Instructions text
     */
    public function getInstructions($templateType) {
        $instructions = [
            'picklist' => '
Picklist Import Instructions:

1. Download the template file
2. Fill in your picklist data:
   - Picklist_Code: Unique identifier (e.g., PT-001, PT-002)
   - Sale_Order_Code: Order reference from Unicommerce
   - Item_SkuCode: Product SKU code
   - Shelf_Code: Warehouse shelf/location
   - Batch_Code: Inventory batch identifier
   - Picklist_Status: IN_PROGRESS, PENDING, or COMPLETED

3. Required fields:
   - Picklist_Code, Sale_Order_Code, Item_SkuCode

4. Optional fields:
   - Item_Type_Name, Shipping_Package_Code, EAN, etc.

5. Save as CSV (comma-separated values)
6. Upload using the Import page
7. Review preview before confirming import

Notes:
- Do not modify header row
- Duplicate picklist codes will be rejected
- Max 10,000 records per import
- Invalid rows will be reported with error details
            ',
            'employee' => '
Employee Import Instructions:

1. Download the template file
2. Fill in employee data:
   - Employee_ID: Unique identifier (e.g., EMP001)
   - Full_Name: Employee full name
   - Email: Email address
   - Phone: Contact number
   - Department: Picking, Packing, QA, etc.
   - Warehouse: Main Warehouse, Secondary Facility
   - Employment_Status: active, inactive, or on_leave
   - Hire_Date: YYYY-MM-DD format

3. Required fields:
   - Employee_ID, Full_Name, Department, Warehouse

4. Optional fields:
   - Email, Phone, Hire_Date

5. Save as CSV (comma-separated values)
6. Upload using the Import page
7. Review preview before confirming import

Notes:
- Employee IDs must be unique
- Dates must be in YYYY-MM-DD format
- Do not modify header row
            '
        ];

        return isset($instructions[$templateType]) ? trim($instructions[$templateType]) : '';
    }
}
?>
