<?php

namespace Src\Models;

use Src\Core\Model;
use Exception;

class PasswordReset extends Model
{
    protected string $table = 'password_resets';

    /**
     * Create a new password reset code for a user
     * 
     * @param int $user_id The user ID
     * @param string $code The reset code
     * @param string $expires_at Expiration datetime (Y-m-d H:i:s)
     * @return bool True on success
     */
    public function createCode(int $user_id, string $code, string $expires_at): bool
    {
        $this->deleteOldCodes($user_id);
        return $this->executeUpdate(
            "INSERT INTO {$this->table} (user_id, code, expires_at) VALUES (?, ?, ?)",
            [$user_id, $code, $expires_at]
        );
    }

    /**
     * Find a reset entry by code
     * 
     * @param string $code The reset code
     * @return array|null The reset entry or null if not found
     */
    public function getByCode(string $code): ?array
    {
        $results = $this->executeQuery(
            "SELECT * FROM {$this->table} WHERE code = ? LIMIT 1",
            [$code]
        );
        return $results[0] ?? null;
    }

    /**
     * Mark a reset code as used
     * 
     * @param int $id The reset entry ID
     * @return bool True on success
     */
    public function markCodeAsUsed(int $id): bool
    {
        return $this->executeUpdate(
            "UPDATE {$this->table} SET used_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    /**
     * Delete old or used reset codes for a user
     * 
     * @param int $user_id The user ID
     * @return bool True on success
     */
    public function deleteOldCodes(int $user_id): bool
    {
        return $this->executeUpdate(
            "DELETE FROM {$this->table} WHERE user_id = ? AND (expires_at < NOW() OR used_at IS NOT NULL)",
            [$user_id]
        );
    }
 public function isCodeValid(array $resetEntry): bool
    {
        if (!$resetEntry) return false;
        if (!empty($resetEntry['used_at'])) return false;
        if (strtotime($resetEntry['expires_at']) < time()) return false;
        return true;
    }
}
