<?php

namespace Src\Models;

use Src\Core\Model;
use PDOException;
use Src\Models\User;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'user_uuid',
        'from_user_uuid',
        'type',
        'reference_type',
        'reference_uuid',
        'is_read',
        'created_at',
        'updated_at'
    ];

    protected array $uuidFields = [
        'user_uuid',
        'from_user_uuid',
        'reference_uuid'
    ];

    protected User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }


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

        // Verify recipient user exists
        $recipient = $this->userModel->findByUuid($data['user_uuid'] ?? '');
        if (!$recipient) {
            throw new \InvalidArgumentException('Recipient user not found');
        }

        // Verify actor user exists
        $actor = $this->userModel->findByUuid($data['actor_uuid'] ?? '');
        if (!$actor) {
            throw new \InvalidArgumentException('Actor user not found');
        }

        // Set default read status and timestamps
        $data['is_read'] = false;
        $data['created_at'] = date('Y-m-d H:i:s');

        // Delegate to parent
        try {
            return parent::create($data);
        } catch (PDOException $e) {
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


    public function getUnread(string $userUuid, int $limit = 10): array
    {
        try {
            $user = $this->userModel->findByUuid($userUuid);
            if (!$user) {
                throw new \InvalidArgumentException('User not found');
            }
            $userId = $user['public_uuid']; // Get the internal ID from the user record

            $notifications = $this->executeQuery(
                "SELECT 
                    n.*, 
                    u.username, 
                    u.profile_picture_url,
                    u.public_uuid as actor_uuid,
                    COALESCE(p.public_uuid, c.public_uuid) as reference_uuid
                 FROM {$this->table} n
                 JOIN users u ON n.from_user_uuid = u.public_uuid
                 LEFT JOIN posts p ON n.reference_type = 'post' AND n.reference_uuid = p.public_uuid
                 LEFT JOIN comments c ON n.reference_type = 'comment' AND n.reference_uuid = c.public_uuid
                 WHERE n.user_uuid = ? AND n.is_read = 0
                 ORDER BY n.created_at DESC
                 LIMIT ?",
                [$userUuid, $limit]
            );

            return array_map([$this, 'formatNotificationResponse'], $notifications);
        } catch (PDOException $e) {
            error_log('Get unread notifications failed: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Format notification response with UUIDs
     */
    private function formatNotificationResponse(array $notification): array
    {
        return [
            'public_uuid' => $notification['public_uuid'] ?? null,
            'user_uuid' => $notification['user_uuid'] ?? null,
            'type' => $notification['type'] ?? null,
            'is_read' => (bool)($notification['is_read'] ?? false),
            'is_hidden' => (bool)($notification['is_hidden'] ?? false),
            'created_at' => $notification['created_at'] ?? null,
            'updated_at' => $notification['updated_at'] ?? null,
            'actor' => [
                'uuid' => $notification['from_user_uuid'] ?? null,
                'username' => $notification['username'] ?? null,
                'profile_picture_url' => $notification['profile_picture_url'] ?? null
            ],
            'reference' => [
                'type' => $notification['reference_type'] ?? null,
                'uuid' => $notification['reference_uuid'] ?? null
            ]
        ];
    }

    public function markAsRead(string $userUuid, array $notificationUuids): bool
    {
        if (empty($notificationUuids)) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $placeholders = str_repeat('?,', count($notificationUuids) - 1) . '?';
            $params = array_merge([$userUuid], $notificationUuids);

            // Update only the notifications that belong to the user and are not already read
            $sql = "UPDATE {$this->table} n
                    INNER JOIN users u ON u.public_uuid = ?
                    SET n.is_read = 1,
                        n.updated_at = NOW()
                    WHERE n.public_uuid IN ({$placeholders})
                    AND n.user_uuid = u.public_uuid
                    AND n.is_read = 0";

            $affectedRows = $this->executeUpdate($sql, $params);

            // If no rows were affected, either the user doesn't exist or notifications were already read
            if ($affectedRows === 0) {
                // Check if user exists
                $userExists = $this->userModel->findByUuid($userUuid);
                if (!$userExists) {
                    throw new \InvalidArgumentException('User not found');
                }
                // If user exists but no rows affected, all notifications were already read
                $this->db->commit();
                return true;
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log('Mark notifications as read failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('Error in markAsRead: ' . $e->getMessage());
            return false;
        }
    }


    public function createFollowNotification(string $recipientUuid, string $followerUuid): int
    {
        return $this->create([
            'user_uuid' => $recipientUuid,
            'actor_uuid' => $followerUuid,
            'type' => 'follow',
            'reference_type' => 'user',
            'reference_uuid' => $followerUuid
        ]);
    }


    public function createLikeNotification(
        string $recipientUuid,
        string $likerUuid,
        string $contentType,
        string $contentUuid
    ): int {
        return $this->create([
            'user_uuid' => $recipientUuid,
            'actor_uuid' => $likerUuid,
            'type' => 'like',
            'reference_type' => $contentType,
            'reference_uuid' => $contentUuid
        ]);
    }

    public function createCommentNotification(
        string $recipientUuid,
        string $commenterUuid,
        string $postUuid
    ): int {
        return $this->create([
            'user_uuid' => $recipientUuid,
            'actor_uuid' => $commenterUuid,
            'type' => 'comment',
            'reference_type' => 'post',
            'reference_uuid' => $postUuid
        ]);
    }

    public function createMentionNotification(
        string $recipientUuid,
        string $mentionerUuid,
        string $contentType,
        string $contentUuid
    ): int {
        return $this->create([
            'user_uuid' => $recipientUuid,
            'actor_uuid' => $mentionerUuid,
            'type' => 'mention',
            'reference_type' => $contentType,
            'reference_uuid' => $contentUuid
        ]);
    }

    public function createPostNotification(
        string $recipientUuid,
        string $posterUuid,
        string $postUuid
    ): int {
        return $this->create([
            'user_uuid' => $recipientUuid,
            'actor_uuid' => $posterUuid,
            'type' => 'post',
            'reference_type' => 'post',
            'reference_uuid' => $postUuid
        ]);
    }

    public function getNotifications(
        string $userUuid,
        int $limit = 10,
        int $offset = 0
    ): array {
        try {
            // Verify user exists and get their notifications
            $user = $this->userModel->findByUuid($userUuid);
            if (!$user) {
                throw new \InvalidArgumentException('User not found');
            }

            // notifications with user details in a single query
            $notifications = $this->executeQuery(
                "SELECT 
                    n.public_uuid,
                    n.user_uuid,
                    n.from_user_uuid,
                    n.type,
                    n.reference_type,
                    n.reference_uuid,
                    n.is_read,
                    n.is_hidden,
                    n.created_at,
                    n.updated_at,
                    u.username,
                    u.profile_picture_url
                FROM {$this->table} n
                JOIN users u ON n.from_user_uuid = u.public_uuid
                WHERE n.user_uuid = ?
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?",
                [$userUuid, $limit, $offset]
            );

            return array_map([$this, 'formatNotificationResponse'], $notifications);
        } catch (PDOException $e) {
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
