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
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
    
            $mentionId = parent::create($filteredData);
    
            // Notifies the mentioned user (only if both user IDs are present and not the same)
            if (isset($filteredData['mentioned_user_id'], $filteredData['from_user_id'])) {
                $contentType = isset($filteredData['post_id']) && $filteredData['post_id'] ? 'post' : 'comment';
                $contentId = $filteredData['post_id'] ?? $filteredData['comment_id'];
    
                $recipientId = $filteredData['mentioned_user_id'];
                $actorId = $filteredData['from_user_id'];
    
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
    
    public function findByMentionedUserId(int $userId): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE mentioned_user_id = ?";
            return $this->executeQuery($sql, [$userId]);
        } catch (\PDOException $e) {
            error_log('Get mentions for user failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getTable(): string
    {
        try {
            return $this->table;
        } catch (PDOException $e) {
            error_log('Get table failed: ' . $e->getMessage());
            return '';
        }
    }
}
