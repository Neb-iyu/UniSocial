<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Comment extends Model
{
    protected string $table = 'comments';
    protected array $fillable = ['post_id', 'user_id', 'content'];

    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
            
            $id = parent::create($filteredData);

            $this->executeUpdate(
                "UPDATE posts 
                 SET comments_count = comments_count + 1 
                 WHERE id = ?",
                [$data['post_id']]
            );

            $this->db->commit();

            // Notify the post owner if not self-comment
            $postModel = new Post();
            $recipientId = $postModel->getOwnerId($data['post_id']);
            if ($recipientId && $recipientId != $data['user_id']) {
                $this->notify('comment', [
                    'recipient_id' => $recipientId,
                    'actor_id' => $data['user_id'],
                    'post_id' => $data['post_id']
                ]);
            }

            // Process mentions 
            if (isset($data['content'])) {
                parent::processMentions($data['content'], $data['user_id'], ['comment_id' => $id]);
            }

            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Comment create failed: ' . $e->getMessage());
            return 0;
        }
    }


    public function delete(int $id): bool
    {
        $this->db->beginTransaction();
        try {
            // Get post_id before deletion using find
            $comment = $this->find($id);

            // Deletes notifications related to this comment
            $notificationModel = new Notification();
            $notificationModel->deleteByCommentId($id);

            if (parent::delete($id)) {
                $this->executeUpdate(
                    "UPDATE posts 
                     SET comments_count = comments_count - 1 
                     WHERE id = ?",
                    [$comment['post_id']]
                );
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Comment delete failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getOwnerId(int $commentId): ?int
    {
        $comment = $this->find($commentId);
        return $comment['user_id'] ?? null;
    }


    public function all(): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, u.public_uuid as user_uuid, p.public_uuid as post_uuid 
                 FROM {$this->table} c
                 JOIN users u ON c.user_id = u.id
                 JOIN posts p ON c.post_id = p.id
                 WHERE c.is_deleted = 0 
                 ORDER BY c.created_at DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Fetch all active comments failed: ' . $e->getMessage());
            return [];
        }
    }

    public function recoverFromPostDeletion(int $postId): bool
    {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE {$this->table} 
                    SET post_deleted = 0, 
                        updated_at = NOW() 
                    WHERE post_id = :postId 
                    AND post_deleted = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':postId', $postId, \PDO::PARAM_INT);
            $success = $stmt->execute();
            
            if ($success) {
                $this->db->commit();
                return true;
            }
            
            $this->db->rollBack();
            return false;
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Recovering comments from post deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark all comments of a post as post_deleted
     * @param int $postId The ID of the post
     * @return bool True on success, false on failure
     */
    public function markAsPostDeleted(int $postId): bool
    {
        $this->db->beginTransaction();
        try {
            $sql = "UPDATE {$this->table} 
                    SET post_deleted = 1, 
                        updated_at = NOW() 
                    WHERE post_id = :postId 
                    AND is_deleted = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':postId', $postId, \PDO::PARAM_INT);
            $success = $stmt->execute();
            
            if ($success) {
                $this->db->commit();
                return true;
            }
            
            $this->db->rollBack();
            return false;
            
        } catch (\PDOException $e) {
            $this->db->rollBack();
            error_log('Marking comments as post_deleted failed: ' . $e->getMessage());
            return false;
        }
    }

    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT c.*, u.public_uuid as user_uuid, p.public_uuid as post_uuid 
                 FROM {$this->table} c
                 JOIN users u ON c.user_id = u.id
                 JOIN posts p ON c.post_id = p.id
                 WHERE c.public_uuid = :uuid AND c.is_deleted = 0 
                 LIMIT 1"
            );
            $stmt->bindValue(':uuid', $uuid, \PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Comment lookup failed for uuid {$uuid}: " . $e->getMessage());
            return null;
        }
    }
}
