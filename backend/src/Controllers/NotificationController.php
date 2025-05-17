<?php

namespace Src\Controllers;

use Src\Models\Notification;
use Src\Core\Response;
use Src\Core\Auth;

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
        $notification['reference_uuid'] = $this->userModel->getUuidFromId($notification['reference_id']);
        $notification['profile_picture_url'] = $this->userModel->getProfilePictureUrl($notification['from_user_id']);

        unset($notification['user_id']);
        unset($notification['from_user_id']);
        unset($notification['reference_id']);

        return [
            'public_uuid' => $notification['public_uuid'] ?? null,
            'user_uuid' => $notification['user_uuid'] ?? null,
            'from_user_uuid' => $notification['from_user_uuid'] ?? null,
            'type' => $notification['type'] ?? null,
            'is_read' => (bool)($notification['is_read'] ?? false),
            'created_at' => $notification['created_at'] ?? null,
            'updated_at' => $notification['updated_at'] ?? null,
            'actor' => [
                'username' => $notification['username'] ?? null,
                'profile_picture_url' => $notification['profile_picture_url'] ?? null
            ],
            'reference' => [
                'type' => $notification['reference_type'] ?? null,
                'uuid' => $notification['reference_uuid'] ?? null
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
