<?php
/**
 * Validators
 * Server-side validation rules for all inputs
 */

namespace WPMS\Helpers;

class Validators
{
    /**
     * Validate employee creation data
     */
    public static function validateEmployeeCreate(array $data): array
    {
        $errors = [];

        // emp_id - required, alphanumeric, 3-50 chars
        if (empty($data['emp_id'])) {
            $errors['emp_id'] = 'Employee ID is required';
        } elseif (strlen($data['emp_id']) < 3 || strlen($data['emp_id']) > 50) {
            $errors['emp_id'] = 'Employee ID must be 3-50 characters';
        } elseif (!preg_match('/^[A-Z0-9\-\_]+$/i', $data['emp_id'])) {
            $errors['emp_id'] = 'Employee ID must contain only alphanumeric characters, hyphens, and underscores';
        }

        // full_name - required, 2-255 chars
        if (empty($data['full_name'])) {
            $errors['full_name'] = 'Full name is required';
        } elseif (strlen($data['full_name']) < 2 || strlen($data['full_name']) > 255) {
            $errors['full_name'] = 'Full name must be 2-255 characters';
        }

        // email - optional but must be valid if provided
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email address';
            }
        }

        // phone - optional but must be numeric if provided
        if (!empty($data['phone'])) {
            if (!preg_match('/^[0-9\+\-\(\)\s]+$/', $data['phone'])) {
                $errors['phone'] = 'Invalid phone number format';
            }
        }

        // warehouse_id - required, must be positive integer
        if (empty($data['warehouse_id'])) {
            $errors['warehouse_id'] = 'Warehouse is required';
        } elseif (!is_numeric($data['warehouse_id']) || $data['warehouse_id'] <= 0) {
            $errors['warehouse_id'] = 'Invalid warehouse';
        }

        // department_id - required, must be positive integer
        if (empty($data['department_id'])) {
            $errors['department_id'] = 'Department is required';
        } elseif (!is_numeric($data['department_id']) || $data['department_id'] <= 0) {
            $errors['department_id'] = 'Invalid department';
        }

        // employment_status - optional, must be valid enum value
        if (!empty($data['employment_status'])) {
            if (!in_array($data['employment_status'], ['active', 'inactive', 'on_leave'])) {
                $errors['employment_status'] = 'Invalid employment status';
            }
        }

        // hire_date - optional but must be valid date
        if (!empty($data['hire_date'])) {
            if (!self::isValidDate($data['hire_date'])) {
                $errors['hire_date'] = 'Invalid hire date format (YYYY-MM-DD)';
            }
        }

        return $errors;
    }

    /**
     * Validate picking task creation
     */
    public static function validatePickingTaskCreate(array $data): array
    {
        $errors = [];

        // picklist_code - required, unique
        if (empty($data['picklist_code'])) {
            $errors['picklist_code'] = 'Picklist code is required';
        } elseif (strlen($data['picklist_code']) > 100) {
            $errors['picklist_code'] = 'Picklist code must be 100 characters or less';
        }

        // employee_id - required
        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required';
        } elseif (!is_numeric($data['employee_id']) || $data['employee_id'] <= 0) {
            $errors['employee_id'] = 'Invalid employee';
        }

        // order_number - required
        if (empty($data['order_number'])) {
            $errors['order_number'] = 'Order number is required';
        } elseif (strlen($data['order_number']) > 100) {
            $errors['order_number'] = 'Order number must be 100 characters or less';
        }

        // sku_code - required
        if (empty($data['sku_code'])) {
            $errors['sku_code'] = 'SKU code is required';
        } elseif (strlen($data['sku_code']) > 100) {
            $errors['sku_code'] = 'SKU code must be 100 characters or less';
        }

        // quantity_ordered - required, positive integer
        if (empty($data['quantity_ordered'])) {
            $errors['quantity_ordered'] = 'Quantity is required';
        } elseif (!is_numeric($data['quantity_ordered']) || $data['quantity_ordered'] <= 0) {
            $errors['quantity_ordered'] = 'Quantity must be a positive number';
        }

        return $errors;
    }

    /**
     * Validate packing task creation
     */
    public static function validatePackingTaskCreate(array $data): array
    {
        $errors = [];

        // package_code - required, unique
        if (empty($data['package_code'])) {
            $errors['package_code'] = 'Package code is required';
        } elseif (strlen($data['package_code']) > 100) {
            $errors['package_code'] = 'Package code must be 100 characters or less';
        }

        // employee_id - required
        if (empty($data['employee_id'])) {
            $errors['employee_id'] = 'Employee is required';
        } elseif (!is_numeric($data['employee_id']) || $data['employee_id'] <= 0) {
            $errors['employee_id'] = 'Invalid employee';
        }

        // shipment_id - required
        if (empty($data['shipment_id'])) {
            $errors['shipment_id'] = 'Shipment ID is required';
        } elseif (strlen($data['shipment_id']) > 100) {
            $errors['shipment_id'] = 'Shipment ID must be 100 characters or less';
        }

        // order_number - required
        if (empty($data['order_number'])) {
            $errors['order_number'] = 'Order number is required';
        }

        // quantity_items - required, positive integer
        if (empty($data['quantity_items'])) {
            $errors['quantity_items'] = 'Quantity is required';
        } elseif (!is_numeric($data['quantity_items']) || $data['quantity_items'] <= 0) {
            $errors['quantity_items'] = 'Quantity must be a positive number';
        }

        return $errors;
    }

    /**
     * Validate notes/comments
     */
    public static function validateNotes(string $notes): array
    {
        $errors = [];

        if (empty($notes)) {
            $errors['notes'] = 'Notes cannot be empty';
        } elseif (strlen($notes) > 5000) {
            $errors['notes'] = 'Notes must be 5000 characters or less';
        }

        return $errors;
    }

    /**
     * Validate quantity
     */
    public static function validateQuantity(int $quantity): array
    {
        $errors = [];

        if ($quantity <= 0) {
            $errors['quantity'] = 'Quantity must be greater than 0';
        } elseif ($quantity > 10000) {
            $errors['quantity'] = 'Quantity seems unusually large';
        }

        return $errors;
    }

    /**
     * Check if date is valid (YYYY-MM-DD format)
     */
    public static function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Sanitize barcode input
     */
    public static function sanitizeBarcode(string $barcode): string
    {
        return trim(preg_replace('/[^\w\-]/', '', $barcode));
    }
}
