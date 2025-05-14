<?php

namespace Src\Controllers;

use Src\Models\Notification;
use Src\Core\Response;
use Src\Core\Auth;

class NotificationController
{
    // GET /notifications
    public function getAllNotifications()
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $notificationModel = new Notification();
        $notifications = $notificationModel->getNotifications($currentUser['id'], 20, 0);
        Response::success($notifications, 'Notifications fetched successfully');
    }

    // GET /notifications/{id}
    public function getNotificationById($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $notificationModel = new Notification();
        $notification = $notificationModel->find($id);
        if ($notification && !$notification['is_deleted'] && $notification['user_id'] == $currentUser['id']) {
            Response::success($notification, 'Notification found');
        } else {
            Response::notFound('Notification not found');
        }
    }

    // POST /notifications
    public function createNotification()
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationModel = new Notification();
        $notificationId = $notificationModel->create($input);
        if ($notificationId) {
            $notification = $notificationModel->find($notificationId);
            Response::success($notification, 'Notification created', 201);
        } else {
            Response::error('Notification creation failed', 500);
        }
    }

    // PATCH /notifications/{id}
    public function updateNotification($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $notificationModel = new Notification();
        $success = $notificationModel->Update($id, $input);
        if ($success) {
            $notification = $notificationModel->find($id);
            Response::success($notification, 'Notification updated');
        } else {
            Response::error('Notification update failed or no valid fields provided', 400);
        }
    }

    // DELETE /notifications/{id}
    public function deleteNotification($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $notificationModel = new Notification();
        $success = $notificationModel->Delete($id);
        if ($success) {
            Response::success(null, 'Notification deleted');
        } else {
            Response::error('Notification deletion failed', 500);
        }
    }
}
