<?php

namespace Src\Utilities;

use Exception;

class FileUploader
{

    private const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'video/mp4',
        'video/webm',
        'video/ogg'
    ];

    private const MAX_SIZE = 5242880; // 5MB

    private const VALID_DIRS = ['profiles', 'posts'];


    public static function upload(array $file, string $type, ?array $allowedTypes = null, ?int $maxSize = null): array
    {
        try {
            self::validateType($type);
            self::validateFileArray($file);
            self::validateFileSize($file, $maxSize ?? self::MAX_SIZE);
            $mime = self::getMimeType($file['tmp_name']);
            self::validateMimeType($mime, $allowedTypes ?? self::ALLOWED_TYPES);
            $ext = getFileExtension($file['name']);
            $filename = self::generateUniqueFilename($ext);
            $targetDir = self::getTargetDir($type);
            self::ensureDir($targetDir);
            $targetPath = $targetDir . $filename;
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file.');
            }
            $relativePath = 'uploads/' . $type . '/' . $filename;
            $absoluteUrl = self::getAbsoluteUrl($relativePath);
            return ['success' => true, 'path' => $relativePath, 'absolute_url' => $absoluteUrl];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Returns the absolute URL for a relative file path.
     * @param string $relativePath
     * @return string
     */
    public static function getAbsoluteUrl(string $relativePath): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $relativePath = ltrim($relativePath, '/');
        return "$scheme://$host/$relativePath";
    }

    private static function validateType(string $type): void
    {
        if (!in_array($type, self::VALID_DIRS, true)) {
            throw new Exception('Invalid upload type.');
        }
    }

    private static function validateFileArray(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception('No file uploaded.');
        }
    }

    private static function validateFileSize(array $file, int $maxSize): void
    {
        if ($file['size'] > $maxSize) {
            throw new Exception('File too large.');
        }
    }

    private static function getMimeType(string $tmpName): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpName);
        finfo_close($finfo);
        return $mime;
    }

    private static function validateMimeType(string $mime, array $allowedTypes): void
    {
        if (!in_array($mime, $allowedTypes, true)) {
            throw new Exception('Invalid file type.');
        }
    }

    private static function generateUniqueFilename(string $ext): string
    {
        $unique = randomString(16);
        return $unique . '.' . $ext;
    }

    private static function getTargetDir(string $type): string
    {
        return __DIR__ . '/../../public/uploads/' . $type . '/';
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}
