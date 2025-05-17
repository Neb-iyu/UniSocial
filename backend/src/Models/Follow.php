<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Follow extends Model
{
    protected string $table = 'follows';
    protected array $fillable = ['follower_id', 'followed_id'];


    public function followToggle(int $followerId, int $followedId): array
    {
        if ($followerId == $followedId) {
            return ['success' => false, 'action' => 'following', 'message' => 'You cannot follow yourself.'];
        }
        $exists = $this->executeQuery(
            "SELECT 1 FROM {$this->table} WHERE follower_id = ? AND followed_id = ? LIMIT 1",
            [$followerId, $followedId]
        );
        if (!empty($exists)) {
            // Already following, so unfollow
            return $this->unfollow($followerId, $followedId);
        } else {
            // Not following, so follow
            return $this->follow($followerId, $followedId);
        }
    }


    private function follow(int $followerId, int $followedId): array
    {

        try {
            $result = $this->executeUpdate(
                "INSERT INTO {$this->table} (follower_id, followed_id) VALUES (?, ?)",
                [$followerId, $followedId]
            );
            if ($result) {
                // Increments following_count for follower
                $this->executeUpdate(
                    "UPDATE users SET following_count = following_count + 1 WHERE id = ?",
                    [$followerId]
                );
                // Increments followers_count for followed user
                $this->executeUpdate(
                    "UPDATE users SET followers_count = followers_count + 1 WHERE id = ?",
                    [$followedId]
                );
                $this->notify('follow', [
                    'recipient_id' => $followedId,
                    'actor_id' => $followerId
                ]);
                return ['success' => true, 'action' => 'following', 'message' => 'Followed successfully.'];
            } else {
                return ['success' => false, 'action' => 'following', 'message' => 'Failed to follow user.'];
            }
        } catch (PDOException $e) {
            error_log('Follow failed: ' . $e->getMessage());
            return ['success' => false, 'action' => 'following', 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

 
    private function unfollow(int $followerId, int $followedId): array
    {
        
        try {
            $result = $this->executeUpdate(
                "DELETE FROM {$this->table} WHERE follower_id = ? AND followed_id = ?",
                [$followerId, $followedId]
            );
            if ($result) {
                // Decrements following_count for follower
                $this->executeUpdate(
                    "UPDATE users SET following_count = GREATEST(following_count - 1, 0) WHERE id = ?",
                    [$followerId]
                );
                // Decrements followers_count for followed user
                $this->executeUpdate(
                    "UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?",
                    [$followedId]
                );
                return ['success' => true, 'action' => 'unfollowing', 'message' => 'Unfollowed successfully.'];
            } else {
                return ['success' => false, 'action' => 'unfollowing', 'message' => 'Failed to unfollow user.'];
            }
        } catch (PDOException $e) {
            error_log('Unfollow failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
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
