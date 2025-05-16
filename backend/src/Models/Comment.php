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
            $id = parent::create($data);

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
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_deleted = 0 ORDER BY created_at DESC");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Fetch all active comments failed: ' . $e->getMessage());
            return [];
        }
    }

    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE public_uuid = :uuid AND is_deleted = 0 LIMIT 1"
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
