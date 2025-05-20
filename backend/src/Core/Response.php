<?php

namespace Src\Core;

class Response
{
    public static function json(array $data, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        echo json_encode($data);
    }

    public static function success($data = null, string $message = 'OK', int $status = 200): void
    {
        self::json([
            'success' => true,
            'data' => $data,
            'message' => $message
        ], $status);
    }

    public static function error(string $error, int $status = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'error' => $error,
            'errors' => $errors
        ], $status);
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        self::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): void
    {
        self::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): void
    {
        self::error($message, 403);
    }

    public static function validationError(array $errors, string $message = 'Validation Error'): void
    {
        self::error($message, 422, $errors);
    }
}
