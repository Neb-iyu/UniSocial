<?php

namespace Src\Models;

use Src\Core\Model;
use Src\Models\Comment;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = [
        'user_id',
        'content',
        'media_urls',
        'visibility'
    ];

    public function create(array $data): int
    {
        try {
            if (isset($data['media_urls'])) {
                $data['media_urls'] = json_encode($data['media_urls']);
            }
            $postId = parent::create($data);

            
              // Notify all followers of the post creator
            $followModel = new Follow();
            $followers = $followModel->getFollowers($data['user_id']); // returns array of ['id' => ...]

            foreach ($followers as $follower) {
                // Ensures follower's ID (expected as 'id') exists and is not the post creator themselves
                if (isset($follower['id']) && $follower['id'] != $data['user_id']) {

                    $this->notify('post', [
                        'recipient_id' => $follower['id'], 
                        'actor_id' => $data['user_id'],
                        'post_id' => $postId
                    ]);
                }
            }
            

            // Process mentions 
            if (isset($data['content'])) {
                parent::processMentions($data['content'], $data['user_id'], ['post_id' => $postId]);
            }

            return $postId;
        } catch (\PDOException $e) {
            error_log('Post create failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Gets all soft-deleted posts for a user with days remaining until permanent deletion
     */
    public function getSoftDeletedPostByUser(int $userId): array
    {
        try {
            // 30 days retention period
            $retentionDays = 30;

            $sql = "SELECT 
                        p.public_uuid,
                        GREATEST(0, $retentionDays - DATEDIFF(NOW(), p.deleted_at)) as days_remaining
                    FROM {$this->table} p
                    WHERE p.user_id = :userId 
                    AND p.is_deleted = 1
                    ORDER BY p.deleted_at ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Error fetching soft-deleted posts: ' . $e->getMessage());
            return [];
        }
    }

    public function recover(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            // First, recovers the post
            $sql = "UPDATE {$this->table} 
                    SET is_deleted = 0, 
                        deleted_at = NULL 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $success = $stmt->execute();

            if (!$success) {
                $this->db->rollBack();
                return false;
            }

            // It recovers comments that were marked as post_deleted
            $commentModel = new Comment();
            $commentsRecovered = $commentModel->recoverFromPostDeletion($id);

            if ($commentsRecovered === false) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Post recovery failed: ' . $e->getMessage());
            return false;
        }
    }

    public function softDelete(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            // First, marks the post as deleted
            $sql = "UPDATE {$this->table} SET is_deleted = 1, deleted_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $success = $stmt->execute();

            if (!$success) {
                $this->db->rollBack();
                return false;
            }

            // Then marks all comments for this post as post_deleted
            $commentModel = new Comment();
            $commentsMarked = $commentModel->markAsPostDeleted($id);

            if (!$commentsMarked) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Post soft delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        try {
            if (isset($data['media_urls'])) {
                $data['media_urls'] = json_encode($data['media_urls']);
            }
            return parent::update($id, $data);
        } catch (\PDOException $e) {
            error_log('Post update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getFeed(int $userId): array
    {
        try {
            return $this->executeQuery(
                "SELECT p.*, u.username, u.profile_picture_url
                 FROM {$this->table} p
                 JOIN users u ON p.user_id = u.id
                 LEFT JOIN follows f ON p.user_id = f.followed_id
                 WHERE (p.user_id = ? OR f.follower_id = ?)
                 AND p.is_deleted = 0
                 ORDER BY p.created_at DESC",
                [$userId, $userId]
            );
        } catch (\PDOException $e) {
            error_log('Get feed failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getOwnerUuid(int $postId): ?string
    {
        $post = $this->find($postId);
        return $post['user_uuid'] ?? null;
    }


    public function allActive(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_deleted = 0");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Fetch all active posts failed: ' . $e->getMessage());
            return [];
        }
    }

    // Empties the recycle bin
    public function deleteOldSoftDeleted(): int
    {
        $this->db->beginTransaction();
        try {
            // Find posts that have been soft-deleted for more than 30 days
            $stmt = $this->db->prepare(
                "SELECT id, user_id FROM {$this->table} 
                WHERE is_deleted = 1 
                AND deleted_at IS NOT NULL 
                AND deleted_at <= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $stmt->execute();
            $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($posts)) {
                $this->db->commit();
                return 0;
            }

            $postIds = array_column($posts, 'id');
            $placeholders = rtrim(str_repeat('?,', count($postIds)), ',');

            // 1. Delete related notifications
            $notificationModel = new \Src\Models\Notification();
            $stmt = $this->db->prepare(
                "DELETE FROM notifications 
                WHERE reference_type = 'post' 
                AND reference_id IN ({$placeholders})"
            );
            $stmt->execute($postIds);

            // 2. Deletes related comments
            $commentModel = new Comment();
            $stmt = $this->db->prepare("DELETE FROM comments WHERE post_id IN ({$placeholders})");
            $stmt->execute($postIds);

            // 3. Deletes related likes
            $stmt = $this->db->prepare("DELETE FROM likes WHERE post_id IN ({$placeholders})");
            $stmt->execute($postIds);

            // 4. Deletes related mentions
            $stmt = $this->db->prepare("DELETE FROM mentions WHERE post_id IN ({$placeholders})");
            $stmt->execute($postIds);

            // 5. Deletes the posts
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id IN ({$placeholders})");
            $stmt->execute($postIds);

            $deletedCount = $stmt->rowCount();

            // Update user's post counts
            foreach ($posts as $post) {
                if (!empty($post['user_id'])) {
                    $this->executeUpdate(
                        "UPDATE users SET post_count = post_count - 1 WHERE id = ? AND post_count > 0",
                        [$post['user_id']]
                    );
                }
            }

            $this->db->commit();
            return $deletedCount;
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Error permanently deleting old soft-deleted posts: ' . $e->getMessage());
            return 0;
        }
    }

    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, u.public_uuid as user_uuid 
                 FROM {$this->table} p
                 JOIN users u ON p.user_id = u.id
                 WHERE p.public_uuid = :uuid AND p.is_deleted = 0 
                 LIMIT 1"
            );
            $stmt->bindValue(':uuid', $uuid, \PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Post lookup failed for uuid {$uuid}: " . $e->getMessage());
            return null;
        }
    }
}
