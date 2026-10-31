<?php
/**
 * ResponseFormatter
 * Consistent response formatting for API endpoints
 */

namespace WPMS\Helpers;

class ResponseFormatter
{
    /**
     * Format success response
     */
    public static function success(array $data = [], string $message = 'Success', int $code = 200): array
    {
        return [
            'success' => true,
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Format error response
     */
    public static function error(string $message, int $code = 400, array $errors = []): array
    {
        return [
            'success' => false,
            'code' => $code,
            'message' => $message,
            'errors' => $errors
        ];
    }

    /**
     * Format validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): array
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Format paginated response
     */
    public static function paginated(array $data, array $pagination, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'code' => 200,
            'message' => $message,
            'data' => $data,
            'pagination' => $pagination
        ];
    }
}
