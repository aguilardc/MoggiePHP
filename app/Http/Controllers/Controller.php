<?php

namespace App\Http\Controllers;

/**
 * Class Controller
 *
 * @package \App\Http\Controllers
 */
class Controller
{
    /**
     * Base controller class that other controllers can extend.
     */
    public function __construct()
    {
        // Initialize common controller functionality
    }

    /**
     * Validate request data.
     */
    protected function validate(Request $request, array $rules, array $messages = []): array
    {
        return validator($request->all(), $rules, $messages)->validate();
    }

    /**
     * Return a JSON response.
     */
    protected function json(array $data, int $status = 200): JsonResponse
    {
        return response()->json($data, $status);
    }

    /**
     * Return a success response.
     */
    protected function success(string $message = 'Success', array $data = []): JsonResponse
    {
        return response()->json()->success($message, $data);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message = 'Error', int $status = 400): JsonResponse
    {
        return response()->json(['error' => $message], $status);
    }

    /**
     * Return a not found response.
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return response()->json()->notFound($message);
    }

    /**
     * Return an unauthorized response.
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return response()->json()->unauthorized($message);
    }

    /**
     * Return a validation error response.
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return response()->json()->validationError($errors, $message);
    }
}
