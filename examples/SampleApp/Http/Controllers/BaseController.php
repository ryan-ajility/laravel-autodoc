<?php

namespace App\Http\Controllers;

/**
 * Base controller for all application controllers.
 *
 * Provides common functionality and utilities that all controllers
 * can inherit, including response formatting, validation helpers,
 * and standardized error handling.
 */
abstract class BaseController
{
    /**
     * The default pagination limit.
     */
    protected int $defaultLimit = 15;

    /**
     * Format a successful JSON response.
     *
     * Creates a standardized response structure for successful operations.
     * All child controllers should use this method for consistent API responses.
     *
     * @param  mixed  $data  The data to return in the response
     * @param  string  $message  Optional success message
     * @param  int  $statusCode  HTTP status code (default: 200)
     * @return array The formatted response array
     */
    protected function successResponse(mixed $data, string $message = 'Success', int $statusCode = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $statusCode,
        ];
    }

    /**
     * Format an error JSON response.
     *
     * Creates a standardized response structure for error conditions.
     *
     * @param  string  $message  The error message
     * @param  int  $statusCode  HTTP status code (default: 400)
     * @param  array  $errors  Optional array of detailed validation errors
     * @return array The formatted error response array
     */
    protected function errorResponse(string $message, int $statusCode = 400, array $errors = []): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status_code' => $statusCode,
        ];
    }

    /**
     * Validate the incoming request data.
     *
     * This method should be implemented by child controllers to provide
     * specific validation rules for their operations.
     *
     * @param  array  $data  The data to validate
     * @param  array  $rules  The validation rules
     * @return bool True if validation passes
     */
    abstract protected function validate(array $data, array $rules): bool;
}
