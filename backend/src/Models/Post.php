<?php

namespace Src\Models;

use Src\Core\Model;

class Post extends Model
{
    protected string $table = 'posts';
    protected array $fillable = [
        'user_id',
        'content',
        'media_urls',
        'visibility'
    ];

    public function create(array $data): int
    {
        try {
            if (isset($data['media_urls'])) {
                $data['media_urls'] = json_encode($data['media_urls']);
            }
            $postId = parent::create($data);

            // Notify all followers of the post creator
            $followModel = new \Src\Models\Follow();
            $followers = $followModel->getFollowers($data['user_id']); // returns array of ['follower_id' => ...]
            foreach ($followers as $follower) {
                if ($follower['follower_id'] != $data['user_id']) { // avoid self-notification
                    $this->notify('post', [
                        'recipient_id' => $follower['follower_id'],
                        'actor_id' => $data['user_id'],
                        'post_id' => $postId
                    ]);
                }
            }

            // Process mentions 
            if (isset($data['content'])) {
                parent::processMentions($data['content'], $data['user_id'], ['post_id' => $postId]);
            }

            return $postId;
        } catch (\PDOException $e) {
            error_log('Post create failed: ' . $e->getMessage());
            return 0;
        }
    }


    public function update(int $id, array $data): bool
    {
        try {
            if (isset($data['media_urls'])) {
                $data['media_urls'] = json_encode($data['media_urls']);
            }
            return parent::update($id, $data);
        } catch (\PDOException $e) {
            error_log('Post update failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getFeed(int $userId): array
    {
        try {
            return $this->executeQuery(
                "SELECT p.*, u.username, u.profile_image_url
                 FROM {$this->table} p
                 JOIN users u ON p.user_id = u.id
                 LEFT JOIN follows f ON p.user_id = f.followed_id
                 WHERE (p.user_id = ? OR f.follower_id = ?)
                 AND p.is_deleted = 0
                 ORDER BY p.created_at DESC",
                [$userId, $userId]
            );
        } catch (\PDOException $e) {
            error_log('Get feed failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getOwnerId(int $postId): ?int
    {
        $post = $this->find($postId);
        return $post['user_id'] ?? null;
    }


    public function allActive(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_deleted = 0");
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Fetch all active posts failed: ' . $e->getMessage());
            return [];
        }
    }

    // Empties the recycle bin
    public function deleteOldSoftDeleted(): int
    {
        try {
            // Find posts to be deleted
            $stmt = $this->db->prepare(
                "SELECT id FROM {$this->table} WHERE is_deleted = 1 AND deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 2 WEEK)"
            );
            $stmt->execute();
            $posts = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            // Delete notifications for each post
            $notificationModel = new \Src\Models\Notification();
            foreach ($posts as $postId) {
                $notificationModel->deleteByPostId($postId);
            }

            // Now delete the posts
            $stmt = $this->db->prepare(
                "DELETE FROM {$this->table} WHERE is_deleted = 1 AND deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 2 WEEK)"
            );
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            error_log('Permanent post cleanup failed: ' . $e->getMessage());
            return 0;
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
            error_log("Post lookup failed for uuid {$uuid}: " . $e->getMessage());
            return null;
        }
    }


}
