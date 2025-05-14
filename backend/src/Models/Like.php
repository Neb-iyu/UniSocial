<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Like extends Model
{
    protected string $table = 'likes';
    protected string $primaryKey = 'id';
    protected array $fillable = ['user_id', 'post_id', 'comment_id'];

    public function likeToggle(int $userId, ?int $postId = null, ?int $commentId = null): bool
    {
        if (($postId === null && $commentId === null) || ($postId !== null && $commentId !== null)) {
            throw new \InvalidArgumentException('Either postId or commentId must be provided, but not both.');
        }
        $this->db->beginTransaction();
        try {
            $query = "SELECT 1 FROM {$this->table} WHERE user_id = ? AND ";
            $params = [$userId];
            if ($postId !== null) {
                $query .= "post_id = ? AND comment_id IS NULL";
                $params[] = $postId;
            } else {
                $query .= "post_id IS NULL AND comment_id = ?";
                $params[] = $commentId;
            }

            $exists = $this->executeQuery($query, $params);

            if ($exists) {
                $this->removeLike($userId, $postId, $commentId);
                $this->db->commit();
                return false;
            }

            $this->addLike($userId, $postId, $commentId);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Like toggle failed: ' . $e->getMessage());
            return false;
        }
    }

    private function addLike(int $userId, ?int $postId, ?int $commentId): void
    {
        if (($postId === null && $commentId === null) || ($postId !== null && $commentId !== null)) {
            throw new \InvalidArgumentException('Either postId or commentId must be provided, but not both.');
        }
        $this->db->beginTransaction();
        try {
            $this->executeUpdate(
                "INSERT INTO {$this->table} (user_id, post_id, comment_id) VALUES (?, ?, ?)",
                [$userId, $postId, $commentId]
            );

            $table = $postId !== null ? 'posts' : 'comments';
            $id = $postId ?? $commentId;

            $this->executeUpdate(
                "UPDATE {$table} SET likes_count = likes_count + 1 WHERE id = ?",
                [$id]
            );
            $this->db->commit();

            // Notify the owner if not self-like
            if ($postId !== null) {
                $recipientId = $this->getPostOwnerId($postId);
                if ($recipientId && $recipientId != $userId) {
                    $this->notify('like', [
                        'recipient_id' => $recipientId,
                        'actor_id' => $userId,
                        'content_type' => 'post',
                        'content_id' => $postId
                    ]);
                }
            } else {
                $recipientId = $this->getCommentOwnerId($commentId);
                if ($recipientId && $recipientId != $userId) {
                    $this->notify('like', [
                        'recipient_id' => $recipientId,
                        'actor_id' => $userId,
                        'content_type' => 'comment',
                        'content_id' => $commentId
                    ]);
                }
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Add like failed: ' . $e->getMessage());
        }
    }

    private function removeLike(int $userId, ?int $postId, ?int $commentId): void
    {
        if (($postId === null && $commentId === null) || ($postId !== null && $commentId !== null)) {
            throw new \InvalidArgumentException('Either postId or commentId must be provided, but not both.');
        }
        $this->db->beginTransaction();
        try {
            $query = "DELETE FROM {$this->table} WHERE user_id = ? AND ";
            $params = [$userId];
            if ($postId !== null) {
                $query .= "post_id = ? AND comment_id IS NULL";
                $params[] = $postId;
            } else {
                $query .= "post_id IS NULL AND comment_id = ?";
                $params[] = $commentId;
            }

            $this->executeUpdate($query, $params);

            $table = $postId !== null ? 'posts' : 'comments';
            $id = $postId ?? $commentId;

            $this->executeUpdate(
                "UPDATE {$table} SET likes_count = likes_count - 1 WHERE id = ?",
                [$id]
            );
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Remove like failed: ' . $e->getMessage());
        }
    }

    public function getLikesForPost(int $postId): array
    {
        try {
            return $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE post_id = ? AND comment_id IS NULL",
                [$postId]
            );
        } catch (PDOException $e) {
            error_log('Get likes for post failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getLikesForComment(int $commentId): array
    {
        try {
            return $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE comment_id = ? AND post_id IS NULL",
                [$commentId]
            );
        } catch (PDOException $e) {
            error_log('Get likes for comment failed: ' . $e->getMessage());
            return [];
        }
    }

    public function countLikesForPost(int $postId): int
    {
        try {
            $result = $this->executeQuery(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE post_id = ? AND comment_id IS NULL",
                [$postId]
            );
            return $result[0]['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Count likes for post failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function countLikesForComment(int $commentId): int
    {
        try {
            $result = $this->executeQuery(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE comment_id = ? AND post_id IS NULL",
                [$commentId]
            );
            return $result[0]['count'] ?? 0;
        } catch (PDOException $e) {
            error_log('Count likes for comment failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getPostOwnerId(int $postId): ?int
    {
        $postModel = new \Src\Models\Post();
        return $postModel->getOwnerId($postId);
    }

    public function getCommentOwnerId(int $commentId): ?int
    {
        $commentModel = new \Src\Models\Comment();
        return $commentModel->getOwnerId($commentId);
    }

}
