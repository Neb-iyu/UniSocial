<?php

namespace Src\Core;

use Src\Models\User;
use Firebase\JWT\JWT;

class Auth
{
    public function validateRequest(): ?int
    {
        $token = $this->getBearerToken();
        if (!$token) return null;

        try {
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($_ENV['JWT_SECRET'], 'HS256'));
            return $decoded->user_id;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getCurrentUser(): ?array
    {
        $userId = $this->validateRequest();
        if (!$userId) return null;
        $userModel = new User();
        $user = $userModel->find($userId);

        // Opportunistic post cleanup: run once per day
        $cacheFile = __DIR__ . '/../../cleanup_cache.txt';
        $now = time();
        $lastCleanup = 0;
        if (file_exists($cacheFile)) {
            $lastCleanup = (int)file_get_contents($cacheFile);
        }
        // 86400 seconds = 24 hours
        if ($now - $lastCleanup > 86400) {
            $postModel = new \Src\Models\Post();
            $postModel->deleteOldSoftDeleted();
            file_put_contents($cacheFile, (string)$now);
        }

        return $user;
    }

    public function generateToken(array $user): string
    {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'exp' => time() + 60 * 60 * 24 * 7 // 1 week
        ];
        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    private function getBearerToken(): ?string
    {
        // Try to get the header from getallheaders() first
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['Authorization']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';
        }

        error_log('Auth header used: ' . $authHeader);

        return preg_match('/Bearer\s(\S+)/', $authHeader, $matches)
            ? $matches[1]
            : null;
    }
}
