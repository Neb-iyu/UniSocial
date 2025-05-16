<?php

namespace Src\Controllers;

use Src\Models\Follow;
use Src\Core\Response;
use Src\Models\User;

class FollowController extends BaseController
{
    private Follow $followModel;

    public function __construct()
    {
        parent::__construct();
        $this->followModel = new Follow();
    }

    // POST /users/{uuid}/follow
    public function follow(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) {
            Response::error('Invalid follow request', 400);
            return;
        }
        $userModel = new User();
        $targetUser = $userModel->findByUuid($uuid);
        if (!$targetUser) {
            Response::error('Invalid follow request', 400);
            return;
        }
        $result = $this->followModel->follow($currentUser['id'], $targetUser['id']);
        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message'], 400);
        }
    }

    // DELETE /users/{uuid}/follow
    public function unfollow(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) {
            Response::error('Invalid unfollow request', 400);
            return;
        }
        $userModel = new User();
        $targetUser = $userModel->findByUuid($uuid);
        if (!$targetUser) {
            Response::error('Invalid unfollow request', 400);
            return;
        }
        $result = $this->followModel->unfollow($currentUser['id'], $targetUser['id']);
        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message'], 400);
        }
    }
}
