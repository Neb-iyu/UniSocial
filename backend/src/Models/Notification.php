<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'user_id',
        'from_user_id',
        'type',
        'reference_type',
        'reference_id'
    ];


    public const TYPE_LIKE = 'like';
    public const TYPE_COMMENT = 'comment';
    public const TYPE_FOLLOW = 'follow';
    public const TYPE_MENTION = 'mention';
    public const TYPE_POST = 'post';

    public const REF_TYPE_POST = 'post';
    public const REF_TYPE_COMMENT = 'comment';
    public const REF_TYPE_USER = 'user';

    public function create(array $data): int
    {

        $validTypes = [
            self::TYPE_LIKE,
            self::TYPE_COMMENT,
            self::TYPE_FOLLOW,
            self::TYPE_MENTION,
            self::TYPE_POST
        ];
        $validReferenceTypes = [
            self::REF_TYPE_POST,
            self::REF_TYPE_COMMENT,
            self::REF_TYPE_USER
        ];

        if (!in_array($data['type'], $validTypes)) {
            throw new \InvalidArgumentException("Invalid notification type");
        }

        if (!in_array($data['reference_type'], $validReferenceTypes)) {
            throw new \InvalidArgumentException("Invalid reference type");
        }

        // Set default read status
        $data['is_read'] = false;
        $data['created_at'] = date('Y-m-d H:i:s');

        // Delegate to parent
        try {
            return parent::create($data);
        } catch (\PDOException $e) {
            error_log('Notification create failed: ' . $e->getMessage());
            throw $e;
        }
    }


    public function update(int $id, array $data): bool
    {
        // Model-specific processing
        if (isset($data['is_read'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Delegate to parent
        return parent::update($id, $data);
    }


    public function getUnread(int $userId, int $limit = 10): array
    {
        try {
            return $this->executeQuery(
                "SELECT n.*, u.username, u.profile_image 
                 FROM {$this->table} n
                 JOIN users u ON n.from_user_id = u.id
                 WHERE n.user_id = ? AND n.is_read = 0
                 ORDER BY n.created_at DESC
                 LIMIT ?",
                [$userId, $limit]
            );
        } catch (\PDOException $e) {
            error_log('Get unread notifications failed: ' . $e->getMessage());
            return [];
        }
    }


    public function markAsRead(int $userId, array $notificationIds): bool
    {
        if (empty($notificationIds)) {
            return false;
        }
        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        try {
            return $this->executeUpdate(
                "UPDATE {$this->table} 
                 SET is_read = 1, 
                     updated_at = NOW()
                 WHERE user_id = ? 
                 AND id IN ($placeholders)",
                array_merge([$userId], $notificationIds)
            );
        } catch (\PDOException $e) {
            error_log('Mark notifications as read failed: ' . $e->getMessage());
            return false;
        }
    }


    public function createFollowNotification(int $recipientId, int $followerId): int
    {
        return $this->create([
            'user_id' => $recipientId,
            'from_user_id' => $followerId,
            'type' => 'follow',
            'reference_type' => 'user',
            'reference_id' => $followerId
        ]);
    }


    public function createLikeNotification(
        int $recipientId,
        int $likerId,
        string $contentType,
        int $contentId
    ): int {
        return $this->create([
            'user_id' => $recipientId,
            'from_user_id' => $likerId,
            'type' => 'like',
            'reference_type' => $contentType,
            'reference_id' => $contentId
        ]);
    }

    public function createCommentNotification(
        int $recipientId,
        int $commenterId,
        int $postId
    ): int {
        return $this->create([
            'user_id' => $recipientId,
            'from_user_id' => $commenterId,
            'type' => 'comment',
            'reference_type' => 'post',
            'reference_id' => $postId
        ]);
    }

    public function createMentionNotification(
        int $recipientId,
        int $mentionerId,
        string $contentType,
        int $contentId
    ): int {
        return $this->create([
            'user_id' => $recipientId,
            'from_user_id' => $mentionerId,
            'type' => 'mention',
            'reference_type' => $contentType,
            'reference_id' => $contentId
        ]);
    }

    public function createPostNotification(
        int $recipientId,
        int $posterId,
        int $postId
    ): int {
        return $this->create([
            'user_id' => $recipientId,
            'from_user_id' => $posterId,
            'type' => 'post',
            'reference_type' => 'post',
            'reference_id' => $postId
        ]);
    }

    public function getNotifications(
        int $userId,
        int $limit = 10,
        int $offset = 0
    ): array {
        try {
            return $this->executeQuery(
                "SELECT n.*, u.username, u.profile_image 
                 FROM {$this->table} n
                 JOIN users u ON n.from_user_id = u.id
                 WHERE n.user_id = ?
                 ORDER BY n.created_at DESC
                 LIMIT ? OFFSET ?",
                [$userId, $limit, $offset]
            );
        } catch (\PDOException $e) {
            error_log('Get notifications failed: ' . $e->getMessage());
            return [];
        }
    }

    public function deleteNotifications(int $userId): bool
    {
        try {
            return $this->executeUpdate(
                "DELETE FROM {$this->table} 
                 WHERE user_id = ?",
                [$userId]
            );
        } catch (\PDOException $e) {
            error_log('Delete notifications failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getNotificationCount(int $userId): int
    {
        try {
            $result = $this->executeQuery(
                "SELECT COUNT(*) as count 
                 FROM {$this->table} 
                 WHERE user_id = ?",
                [$userId]
            );
            return $result[0]['count'] ?? 0;
        } catch (\PDOException $e) {
            error_log('Get notification count failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function getNotificationTypes(): array
    {
        return [
            self::TYPE_LIKE => 'Liked your post',
            self::TYPE_COMMENT => 'Commented on your post',
            self::TYPE_FOLLOW => 'Followed you',
            self::TYPE_MENTION => 'Mentioned you in a post',
            self::TYPE_POST => 'Posted a new post'
        ];
    }

    public function markAllAsRead(int $userId): bool
    {
        try {
            return $this->executeUpdate(
                "UPDATE {$this->table} SET is_read = 1, updated_at = NOW() WHERE user_id = ? AND is_read = 0",
                [$userId]
            );
        } catch (\PDOException $e) {
            error_log('Mark all notifications as read failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteByPostId(int $postId): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE reference_type = 'post' AND reference_id = ?");
            $stmt->execute([$postId]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log('Delete notifications by post failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function deleteByCommentId(int $commentId): int
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE reference_type = 'comment' AND reference_id = ?");
            $stmt->execute([$commentId]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log('Delete notifications by comment failed: ' . $e->getMessage());
            return 0;
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
            error_log("Notification lookup failed for uuid {$uuid}: " . $e->getMessage());
            return null;
        }
    }
}
