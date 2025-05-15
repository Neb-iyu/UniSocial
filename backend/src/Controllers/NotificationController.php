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

    // GET /notifications
    public function getAllNotifications(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notifications = $this->notificationModel->getNotifications($currentUser['id'], 20, 0);
        Response::success($notifications, 'Notifications fetched successfully');
    }

    // GET /notifications/{id}
    public function getNotificationById($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notification = $this->notificationModel->find($id);
        if ($notification && !$notification['is_deleted'] && $notification['user_id'] == $currentUser['id']) {
            Response::success($notification, 'Notification found');
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
            Response::success($notification, 'Notification created', 201);
        } else {
            Response::error('Notification creation failed', 500);
        }
    }

    // PATCH /notifications/{id}
    public function updateNotification($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notification = $this->notificationModel->find($id);
        if (!$notification || $notification['is_deleted']) {
            Response::notFound('Notification not found');
            return;
        }
        if (isset($notification['user_id']) && !$this->requireSelfOrAdmin($currentUser, $notification['user_id'])) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $success = $this->notificationModel->Update($id, $input);
        if ($success) {
            $notification = $this->notificationModel->find($id);
            Response::success($notification, 'Notification updated');
        } else {
            Response::error('Notification update failed or no valid fields provided', 400);
        }
    }

    // DELETE /notifications/{id}
    public function deleteNotification($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $notification = $this->notificationModel->find($id);
        if (!$notification || $notification['is_deleted']) {
            Response::notFound('Notification not found');
            return;
        }
        if (isset($notification['user_id']) && !$this->requireSelfOrAdmin($currentUser, $notification['user_id'])) return;
        $success = $this->notificationModel->Delete($id);
        if ($success) {
            Response::success(null, 'Notification deleted');
        } else {
            Response::error('Notification deletion failed', 500);
        }
    }
}
