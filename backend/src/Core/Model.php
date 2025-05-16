<?php

namespace Src\Core;

use Src\Models\Notification;
use PDO;
use PDOException;

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

    protected function processMentions(string $content, int $fromUserId, array $context = []): void
    {
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) {
            $userModel = new \Src\Models\User();
            $mentionModel = new \Src\Models\Mention();
            foreach ($matches[1] as $username) {
                $user = $userModel->findByUsername($username);
                if ($user && $user['id'] != $fromUserId) {
                    $mentionData = [
                        'mentioned_user_id' => $user['id'],
                        'from_user_id' => $fromUserId
                    ] + $context; // context could be post_id, comment_id, etc.
                    $mentionModel->addMention($mentionData);
                }
            }
        }
    }

    // Notify users of actions (like, comment, follow, post, mention).
    
    protected function notify(string $type, array $data): void
    {
        $notification = new Notification();
        switch ($type) {
            case 'like':
                $notification->createLikeNotification(
                    $data['recipient_id'],
                    $data['actor_id'],
                    $data['content_type'],
                    $data['content_id']
                );
                break;
            case 'comment':
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
            case 'post':
                $notification->createPostNotification(
                    $data['recipient_id'],
                    $data['actor_id'],
                    $data['post_id']
                );
                break;
            case 'mention':
                $notification->createMentionNotification(
                    $data['recipient_id'],
                    $data['actor_id'],
                    $data['content_type'],
                    $data['content_id']
                );
                break;
        }
    }
}
