<?php
/**
 * ResponseHelper Class
 *
 * Provides consistent response formatting for API endpoints
 */

class ResponseHelper {

    /**
     * Send success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return void
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');

        $response = [
            'success' => true,
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Send error response
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @return void
     */
    public static function error($message = 'Error', $code = 400, $errors = []) {
        http_response_code($code);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        echo json_encode($response);
        exit;
    }

    /**
     * Send validation error response
     *
     * @param array $errors Field validation errors
     * @return void
     */
    public static function validationError($errors) {
        self::error('Validation failed', 400, $errors);
    }

    /**
     * Send unauthorized response
     *
     * @param string $message
     * @return void
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     *
     * @param string $message
     * @return void
     */
    public static function forbidden($message = 'Forbidden') {
        self::error($message, 403);
    }

    /**
     * Send not found response
     *
     * @param string $message
     * @return void
     */
    public static function notFound($message = 'Not found') {
        self::error($message, 404);
    }

    /**
     * Send server error response
     *
     * @param string $message
     * @return void
     */
    public static function serverError($message = 'Server error') {
        self::error($message, 500);
    }
}
