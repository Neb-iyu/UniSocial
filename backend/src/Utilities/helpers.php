<?php

namespace Src\Utilities;


if (!function_exists('randomString')) {
    function randomString($length = 16)
    {
        return bin2hex(random_bytes((int)ceil($length / 2)));
    }
}

if (!function_exists('jsonEncodeSafe')) {
    function jsonEncodeSafe($data)
    {
        $json = json_encode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $json;
    }
}

if (!function_exists('jsonDecodeSafe')) {
    function jsonDecodeSafe($json, $assoc = true)
    {
        $data = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }
        return $data;
    }
}

if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('getFileExtension')) {
    function getFileExtension($filename)
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
}
