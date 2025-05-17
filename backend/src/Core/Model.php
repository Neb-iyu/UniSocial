<?php

namespace Src\Core;

use Src\Models\Notification;
use Src\Models\Mention;
use Src\Models\User;
use PDO;

abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }


    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM {$this->table} 
            WHERE {$this->primaryKey} = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} ($columns) 
            VALUES ($placeholders)
        ");
        $stmt->execute(array_values($data));

        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $setClause = implode(' = ?, ', array_keys($data)) . ' = ?';

        $stmt = $this->db->prepare("
            UPDATE {$this->table} 
            SET $setClause 
            WHERE {$this->primaryKey} = ?
        ");

        $values = array_values($data);
        $values[] = $id;

        return $stmt->execute($values);
    }

    protected function executeQuery(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function executeUpdate(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM {$this->table} 
            WHERE {$this->primaryKey} = ?
        ");
        return $stmt->execute([$id]);
    }

    /** Batch processing of mentions with transaction **/
    protected function processMentions(string $content, int $fromUserId, array $context = []): void
    {
        if (empty($content) || $fromUserId <= 0) return;

        // Case-insensitive /i
        if (!preg_match_all('/@([a-z0-9_]+)/i', $content, $matches) || empty($matches[1])) return;


        $usernames = array_unique(array_map('strtolower', $matches[1]));
        if (empty($usernames)) return;


        // Batch fetch mentioned users (excluding self [$fromUserId])
        $placeholders = implode(',', array_fill(0, count($usernames), '?'));
        $params = array_merge($usernames, [$fromUserId]);
        $users = $this->executeQuery(
            "SELECT id, LOWER(username) as username_lower FROM users WHERE LOWER(username) IN ({$placeholders}) AND id != ?",
            $params
        );
        if (empty($users)) return;

        $mentionModel = new Mention();
        $contentId = $context['post_id'] ?? $context['comment_id'] ?? 0;
        $contentType = isset($context['post_id']) ? 'post' : 'comment';

        // Check for existing mentions to prevent duplicates when updating the content
        $existingMentions = [];
        if ($contentId && count($users) > 0) {
            $userIds = array_column($users, 'id');
            $inClause = implode(',', array_fill(0, count($userIds), '?'));
            $existing = $this->executeQuery(
                "SELECT mentioned_user_id FROM mentions 
             WHERE from_user_id = ? AND {$contentType}_id = ? 
             AND mentioned_user_id IN ($inClause)",
                array_merge([$fromUserId, $contentId], $userIds)
            );
            $existingMentions = array_column($existing, 'mentioned_user_id');
        }

        $now = date('Y-m-d H:i:s');
        $this->db->beginTransaction();
        try {
            foreach ($users as $user) {
                if (in_array($user['id'], $existingMentions)) continue;

                $mentionData = [
                    'mentioned_user_id' => $user['id'],
                    'from_user_id' => $fromUserId,
                    'created_at' => $now,
                    'updated_at' => $now
                ] + $context;

                $mentionId = $mentionModel->addMention($mentionData);
                if (!$mentionId) {
                    throw new \RuntimeException("Failed to add mention for user {$user['id']}");
                }
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error processing mentions: ' . $e->getMessage());
        }
    }

    /**
     *  Notify users of actions (like, comment, follow, post, mention).
     * @param string $type The type of notification (like, comment, follow, post, mention)
     * @param array $data {
     *     @type string $recipient_uuid The UUID of the user receiving the notification
     *     @type string $actor_uuid The UUID of the user who triggered the notification
     *     @type string $content_type The type of content being referenced (for like/mention)
     *     @type string $content_uuid The UUID of the content being referenced (for like/mention)
     *     @type string $post_uuid The UUID of the post (for comment/post)
     * }
     */
    protected function notify(string $type, array $data): void
    {
        if (!isset($data['recipient_id'])) {
            throw new \InvalidArgumentException('recipient_id is required');
        }

        if (!isset($data['actor_id'])) {
            throw new \InvalidArgumentException('actor_id is required');
        }

        $notification = new Notification();

        try {
            switch ($type) {
                case 'like':
                    if (!isset($data['content_type'], $data['content_id'])) {
                        throw new \InvalidArgumentException('content_type and content_id are required for like notifications');
                    }
                    $notification->createLikeNotification(
                        $data['recipient_id'],
                        $data['actor_id'],
                        $data['content_type'],
                        $data['content_id']
                    );
                    break;

                case 'comment':
                    if (!isset($data['post_id'])) {
                        throw new \InvalidArgumentException('post_id is required for comment notifications');
                    }
                    $notification->createCommentNotification(
                        $data['recipient_id'],
                        $data['actor_id'],
                        $data['post_id']
                    );
                    break;

                case 'follow':
                    $notification->createFollowNotification(
                        $data['recipient_id'],
                        $data['actor_id']
                    );
                    break;

                case 'mention':
                    if (!isset($data['content_type'], $data['content_id'])) {
                        throw new \InvalidArgumentException('content_type and content_id are required for mention notifications');
                    }
                    $notification->createMentionNotification(
                        $data['recipient_id'],
                        $data['actor_id'],
                        $data['content_type'],
                        $data['content_id']
                    );
                    break;

                case 'post':
                    if (!isset($data['post_id'])) {
                        throw new \InvalidArgumentException('post_id is required for post notifications');
                    }
                    $notification->createPostNotification(
                        $data['recipient_id'],
                        $data['actor_id'],
                        $data['post_id']
                    );
                    break;

                default:
                    throw new \InvalidArgumentException("Unknown notification type: {$type}");
            }
        } catch (\Exception $e) {
            error_log("Failed to create {$type} notification: " . $e->getMessage());
            throw $e;
        }
    }
}
