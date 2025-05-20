<?php

namespace Src\Utilities;

class Validator
{
    public static function email($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function username($username)
    {
        // Alphanumeric, underscores, 5-20 chars
        return preg_match('/^[a-zA-Z0-9_]{5,20}$/', $username);
    }

    public static function password($password)
    {
        // At least 8 chars, one letter, one number
        return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d!@#$%^&*()_+\-=]{8,}$/', $password);
    }

    /**
     * Sanitize a string input (trim, strip tags, remove special chars).
     */
    public static function sanitizeString($value)
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize an email input.
     */
    public static function sanitizeEmail($value)
    {
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
    }

    /**
     * Sanitize an integer input.
     */
    public static function sanitizeInt($value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Recursively sanitize an array (strings, emails, ints).
     */
    public static function sanitizeArray(array $arr)
    {
        $sanitized = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value);
            } elseif (is_int($value)) {
                $sanitized[$key] = self::sanitizeInt($value);
            } elseif (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $sanitized[$key] = self::sanitizeEmail($value);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize input (auto-detects type: array or scalar).
     */
    public static function sanitizeInput($input)
    {
        if (is_array($input)) {
            return self::sanitizeArray($input);
        } elseif (is_int($input)) {
            return self::sanitizeInt($input);
        } elseif (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return self::sanitizeEmail($input);
        } else {
            return self::sanitizeString($input);
        }
    }

    /**
     * Check if a value is in an allowed set (enum).
     */
    public static function inEnum($value, array $allowedValues): bool
    {
        return in_array($value, $allowedValues, true);
    }

    public static function validateFileType(string $mime, array $allowedTypes): void
    {
        if (!in_array($mime, $allowedTypes, true)) {
            throw new \Exception('Invalid file type.');
        }
    }

    public static function validateFileSize(int $size, int $maxSize): void
    {
        if ($size > $maxSize) {
            throw new \Exception('File too large.');
        }
    }

    public static function validateFileArray(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception('No file uploaded.');
        }
    }
}
