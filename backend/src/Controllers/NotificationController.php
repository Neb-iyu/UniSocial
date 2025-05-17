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
        return [
            'public_uuid' => $notification['public_uuid'] ?? null,
            'from_user_uuid' => $notification['from_user_uuid'] ?? null,
            'type' => $notification['type'] ?? null,
            'reference_type' => $notification['reference_type'] ?? null,
            'reference_id' => $notification['reference_id'] ?? null,
            'is_read' => (bool)($notification['is_read'] ?? false),
            'is_hidden' => (bool)($notification['is_hidden'] ?? false),
            'created_at' => $notification['created_at'] ?? null,
            'updated_at' => $notification['updated_at'] ?? null
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
        if ($notification && !$notification['is_deleted'] && $notification['user_id'] == $currentUser['id']) {
            $filteredNotification = $this->filterNotificationResponse($notification);
            Response::success($filteredNotification, 'Notification found');
        } else {
            Response::notFound('Notification not found');
        }
    }

    // POST /notifications
    public function createNotification(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationId = $this->notificationModel->create($input);
        if ($notificationId) {
            $notification = $this->notificationModel->find($notificationId);
            $filteredNotification = $this->filterNotificationResponse($notification);
            Response::success($filteredNotification, 'Notification created', 201);
        } else {
            Response::error('Notification creation failed', 500);
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
        if (isset($notification['user_id']) && !$this->requireSelfOrAdmin($currentUser, $notification['user_id'])) return;
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
