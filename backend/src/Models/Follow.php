<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Follow extends Model
{
    protected string $table = 'follows';
    protected array $fillable = ['follower_id', 'followed_id'];

    public function follow(int $followerId, int $followedId): bool
    {
        try {
            $result = $this->executeUpdate(
                "INSERT IGNORE INTO {$this->table} (follower_id, followed_id) VALUES (?, ?)",
                [$followerId, $followedId]
            );
            if ($result && $followerId != $followedId) {
                $this->notify('follow', [
                    'recipient_id' => $followedId,
                    'actor_id' => $followerId
                ]);
            }
            return $result;
        } catch (PDOException $e) {
            error_log('Follow failed: ' . $e->getMessage());
            return false;
        }
    }

    public function unfollow(int $followerId, int $followedId): bool
    {
        try {
            return $this->executeUpdate(
                "DELETE FROM {$this->table} WHERE follower_id = ? AND followed_id = ?",
                [$followerId, $followedId]
            );
        } catch (PDOException $e) {
            error_log('Unfollow failed: ' . $e->getMessage());
            return false;
        }
    }

    public function isFollowing(int $followerId, int $followedId): bool
    {
        try {
            $result = $this->executeQuery(
                "SELECT 1 FROM {$this->table} WHERE follower_id = ? AND followed_id = ?",
                [$followerId, $followedId]
            );
            return !empty($result);
        } catch (PDOException $e) {
            error_log('isFollowing check failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getFollowersCount(int $userId): int
    {
        try {
            $result = $this->executeQuery(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE followed_id = ?",
                [$userId]
            );
            return $result[0]['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get followers count failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getFollowingCount(int $userId): int
    {
        try {
            $result = $this->executeQuery(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE follower_id = ?",
                [$userId]
            );
            return $result[0]['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Get following count failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getFollowers(int $userId): array
    {
        try {
            return $this->executeQuery(
                "SELECT follower_id FROM {$this->table} WHERE followed_id = ?",
                [$userId]
            );
        } catch (PDOException $e) {
            error_log('Get followers failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getFollowing(int $userId): array
    {
        try {
            return $this->executeQuery(
                "SELECT followed_id FROM {$this->table} WHERE follower_id = ?",
                [$userId]
            );
        } catch (PDOException $e) {
            error_log('Get following failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all follows (no soft delete implemented)
     * @return array
     */
    public function allActive(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Fetch all follows failed: ' . $e->getMessage());
            return [];
        }
    }
}
