<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Like extends Model
{
    protected string $table = 'likes';
    protected string $primaryKey = 'id';
    protected array $fillable = ['user_id', 'post_id', 'comment_id'];

    /**
     * Toggle like for a post or comment.
     * Returns ['success' => bool, 'message' => string]
     */
    public function likeToggle(int $userId, ?int $postId = null, ?int $commentId = null): array
    {
        if (($postId === null && $commentId === null) || ($postId !== null && $commentId !== null)) {
            return ['success' => false, 'message' => 'Either postId or commentId must be provided, but not both.'];
        }

        $this->db->beginTransaction();
        try {
            // Existence and soft-delete check
            if ($postId !== null) {
                $postModel = new \Src\Models\Post();
                $post = $postModel->find($postId);
                if (!$post) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Post not found.'];
                }
                if (!empty($post['is_deleted'])) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Cannot like a deleted post.'];
                }
            } else {
                $commentModel = new \Src\Models\Comment();
                $comment = $commentModel->find($commentId);
                if (!$comment) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Comment not found.'];
                }
                if (!empty($comment['is_deleted'])) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Cannot like a deleted comment.'];
                }
            }

            $query = "SELECT id FROM {$this->table} WHERE user_id = ? AND ";
            $params = [$userId];
            if ($postId !== null) {
                $query .= "post_id = ? AND comment_id IS NULL";
                $params[] = $postId;
            } else {
                $query .= "post_id IS NULL AND comment_id = ?";
                $params[] = $commentId;
            }

            $existingLike = $this->executeQuery($query, $params);

            if (!empty($existingLike)) {
                $result = $this->removeLike($existingLike[0]['id'], $postId, $commentId);
                if ($result !== true) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => $result ?: 'Failed to unlike.'];
                }
                $this->db->commit();
                return ['success' => true, 'message' => 'Unliked successfully.'];
            }

            $result = $this->addLike($userId, $postId, $commentId);
            if ($result !== true) {
                $this->db->rollBack();
                return ['success' => false, 'message' => $result ?: 'Failed to like.'];
            }
            $this->db->commit();
            return ['success' => true, 'message' => 'Liked successfully.'];
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Like toggle failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Add a like. Returns like ID on success, or error message string on failure.
     */
    private function addLike(int $userId, ?int $postId, ?int $commentId)
    {
        if (($postId === null && $commentId === null) || ($postId !== null && $commentId !== null)) {
            return 'Either postId or commentId must be provided, but not both.';
        }

        try {
            // Determine which type of like we're dealing with
            if ($postId !== null) {
                $query = "INSERT INTO {$this->table} (user_id, post_id) VALUES (?, ?)";
                $params = [$userId, $postId];
                $table = 'posts';
                $id = $postId;
            } else {
                $query = "INSERT INTO {$this->table} (user_id, comment_id) VALUES (?, ?)";
                $params = [$userId, $commentId];
                $table = 'comments';
                $id = $commentId;
            }

            // Execute the insert and get the new like ID
            $this->executeUpdate($query, $params);
            $likeId = $this->db->lastInsertId();

            // Update likes count
            $this->executeUpdate(
                "UPDATE {$table} SET likes_count = likes_count + 1 WHERE id = ?",
                [$id]
            );

            // Notify the owner if not self-like
            if ($postId !== null) {

                $postModel = new Post();
                $recipientId = $postModel->find($postId)['user_id'];

                $userModel = new User();
                $actorId = $userModel->find($userId)['id'];

                if ($recipientId && $recipientId != $userId) {
                    $this->notify('like', [
                        'recipient_id' => $recipientId,
                        'actor_id' => $actorId,
                        'content_type' => 'post',
                        'content_id' => $postId,
                        'like_id' => $likeId
                    ]);
                }
            } else {
                $commentModel = new Comment();
                $recipientId = $commentModel->find($commentId)['user_id'];

                $userModel = new User();
                $actorId = $userModel->find($userId)['id'];

                $commentId = $commentModel->find($commentId)['id'];

                if ($recipientId && $recipientId != $userId) {
                    $this->notify('like', [
                        'recipient_id' => $recipientId,
                        'actor_id' => $actorId,
                        'content_type' => 'comment',
                        'content_id' => $commentId,
                        'like_id' => $likeId
                    ]);
                }
            }

            return true;
        } catch (PDOException $e) {
            error_log('Add like failed: ' . $e->getMessage());
            return 'Database error: ' . $e->getMessage();
        }
    }

    private function removeLike(int $likeId, ?int $postId, ?int $commentId)
    {
        try {
            // Delete by primary key (more efficient)
            $this->executeUpdate(
                "DELETE FROM {$this->table} WHERE id = ?",
                [$likeId]
            );

            $table = $postId !== null ? 'posts' : 'comments';
            $id = $postId ?? $commentId;

            $this->executeUpdate(
                "UPDATE {$table} SET likes_count = likes_count - 1 WHERE id = ?",
                [$id]
            );
            return true;
        } catch (PDOException $e) {
            error_log('Remove like failed: ' . $e->getMessage());
            return 'Database error: ' . $e->getMessage();
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
            return (int)($result[0]['count'] ?? 0);
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
            return (int)($result[0]['count'] ?? 0);
        } catch (PDOException $e) {
            error_log('Count likes for comment failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getLikeById(int $likeId): ?array
    {
        try {
            $result = $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE id = ?",
                [$likeId]
            );
            return $result[0] ?? null;
        } catch (PDOException $e) {
            error_log('Get like by ID failed: ' . $e->getMessage());
            return null;
        }
    }
}
