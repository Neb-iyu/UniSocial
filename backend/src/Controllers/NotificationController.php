<?php

namespace Src\Controllers;

use Src\Models\Notification;
use Src\Core\Response;
use Src\Models\Post;
use Src\Models\Comment;

class NotificationController extends BaseController
{
    private Notification $notificationModel;

    public function __construct()
    {
        parent::__construct();
        $this->notificationModel = new Notification();
    }

    private function filterNotificationResponse(array $notification): array
    {

        $notification['user_uuid'] = $this->userModel->getUuidFromId($notification['user_id']);
        $notification['from_user_uuid'] = $this->userModel->getUuidFromId($notification['from_user_id']);
        $notification['profile_picture_url'] = $this->userModel->getProfilePictureUrl($notification['from_user_id']);

        // To determine the reference UUID based on reference_type
        $referenceUuid = null;
        if (isset($notification['reference_type'], $notification['reference_id'])) {
            if ($notification['reference_type'] === 'post') {
                $postModel = new Post();
                $post = $postModel->find($notification['reference_id']);
                $referenceUuid = $post['public_uuid'] ?? null;
            } elseif ($notification['reference_type'] === 'comment') {
                $commentModel = new Comment();
                $comment = $commentModel->find($notification['reference_id']);
                $referenceUuid = $comment['public_uuid'] ?? null;
            } else {
                // fallback (just to make it Future proof)
                if ($notification['reference_type'] === 'user') {
                    $referenceUuid = $this->userModel->getUuidFromId($notification['reference_id']);
                }
            }
        }

        $notification['reference_uuid'] = $referenceUuid;

        unset($notification['user_id']);
        unset($notification['from_user_id']);
        unset($notification['reference_id']);

        // readable message for the notification
        $actorUsername = $notification['username'] ?? 'Someone';
        $referenceType = $notification['reference_type'] ?? '';
        $message = '';
        switch ($notification['type'] ?? '') {
            case 'mention':
                if ($referenceType === 'comment') {
                    $message = "$actorUsername mentioned you in a comment.";
                } elseif ($referenceType === 'post') {
                    $message = "$actorUsername mentioned you in a post.";
                } else {
                    $message = "$actorUsername mentioned you.";
                }
                break;
            case 'follow':
                $message = "$actorUsername started following you.";
                break;
            case 'like':
                if ($referenceType === 'comment') {
                    $message = "$actorUsername liked your comment.";
                } elseif ($referenceType === 'post') {
                    $message = "$actorUsername liked your post.";
                } else {
                    $message = "$actorUsername liked your content.";
                }
                break;
            case 'comment':
                $message = "$actorUsername commented on your post.";
                break;
            case 'post':
                $message = "$actorUsername created a new post.";
                break;
            default:
                $message = "$actorUsername sent you a notification.";
                break;
        }

        return [
            'public_uuid' => $notification['public_uuid'] ?? null,
            'user_uuid' => $notification['user_uuid'] ?? null,
            'from_user_uuid' => $notification['from_user_uuid'] ?? null,
            'type' => $notification['type'] ?? null,
            'is_read' => (bool)($notification['is_read'] ?? false),
            'created_at' => $notification['created_at'] ?? null,
            'updated_at' => $notification['updated_at'] ?? null,
            'message' => $message,
            'actor' => [
                'username' => $notification['username'] ?? null,
                'profile_picture_url' => $notification['profile_picture_url'] ?? null
            ],
            'reference' => [
                'type' => $notification['reference_type'] ?? null,
                'public_uuid' => $notification['reference_uuid'] ?? null
            ]
        ];
    }

    // GET /notifications
    public function getAllNotifications(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notifications = $this->notificationModel->getNotifications($currentUser['id'], 20, 0);
        $filteredNotifications = array_map([$this, 'filterNotificationResponse'], $notifications);
        Response::success($filteredNotifications, 'Notifications fetched successfully');
    }

    // GET /notifications/{uuid}
    public function getNotificationByUuid(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notification = $this->notificationModel->findByUuid($uuid);
        if ($notification && $this->requireSelfOrAdmin($currentUser, $notification['user_id'])) {
            $filteredNotification = $this->filterNotificationResponse($notification);
            Response::success($filteredNotification, 'Notification found');
        } else {
            Response::notFound('Notification not found');
        }
    }


    // PATCH /notifications/{uuid}
    public function updateNotification(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notification = $this->notificationModel->findByUuid($uuid);
        if (!$notification || $notification['is_deleted']) {
            Response::notFound('Notification not found');
            return;
        }
        if (isset($notification['user_uuid']) && !$this->requireSelfOrAdmin($currentUser, $notification['user_uuid'])) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $success = $this->notificationModel->Update($notification['id'], $input);
        if ($success) {
            $notification = $this->notificationModel->findByUuid($uuid);
            $filteredNotification = $this->filterNotificationResponse($notification);
            Response::success($filteredNotification, 'Notification updated');
        } else {
            Response::error('Notification update failed or no valid fields provided', 400);
        }
    }

    // DELETE /notifications/{uuid}
    public function deleteNotification(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notification = $this->notificationModel->findByUuid($uuid);
        if (!$notification || $notification['is_deleted']) {
            Response::notFound('Notification not found');
            return;
        }
        if (isset($notification['user_id']) && !$this->requireSelfOrAdmin($currentUser, $notification['user_id'])) return;
        $success = $this->notificationModel->Delete($notification['id']);
        if ($success) {
            Response::success(null, 'Notification deleted');
        } else {
            Response::error('Notification deletion failed', 500);
        }
    }
}
