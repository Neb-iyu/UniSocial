<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Mention extends Model
{
    protected string $table = 'mentions';
    protected array $fillable = [
        'post_id',
        'comment_id',
        'mentioned_user_id',
        'from_user_id'
    ];

    public function addMention(array $data): int
    {
        try {
            $mentionId = parent::create($data);
            // Notify the mentioned user
            if (isset($data['mentioned_user_id'], $data['from_user_id'])) {
                $contentType = isset($data['post_id']) && $data['post_id'] ? 'post' : 'comment';
                $contentId = $data['post_id'] ?? $data['comment_id'];

                $recipientId = $data['mentioned_user_id'];
                $actorId = $data['from_user_id'];

                if ($recipientId != $actorId) {
                    $this->notify('mention', [
                        'recipient_id' => $recipientId,
                        'actor_id' => $actorId,
                        'content_type' => $contentType,
                        'content_id' => $contentId
                    ]);
                }
            }
            return $mentionId;
        } catch (PDOException $e) {
            error_log('Add mention failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getMentionsForPost(int $postId): array
    {
        try {
            return $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE post_id = ?",
                [$postId]
            );
        } catch (PDOException $e) {
            error_log('Get mentions for post failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getMentionsForComment(int $commentId): array
    {
        try {
            return $this->executeQuery(
                "SELECT * FROM {$this->table} WHERE comment_id = ?",
                [$commentId]
            );
        } catch (PDOException $e) {
            error_log('Get mentions for comment failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all mentions (no soft delete implemented)
     * @return array
     */
    public function allActive(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table}");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Fetch all mentions failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Find a mention by its public UUID
     * @param string $uuid
     * @return array|null
     */
    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE public_uuid = :uuid LIMIT 1"
            );
            $stmt->bindValue(':uuid', $uuid, \PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\PDOException $e) {
            error_log("Mention lookup failed for uuid {$uuid}: " . $e->getMessage());
            return null;
        }
    }
}
