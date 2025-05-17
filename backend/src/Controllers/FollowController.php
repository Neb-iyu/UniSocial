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


    // POST /users/{uuid}/follow (toggle)
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
        $result = $this->followModel->followToggle($currentUser['id'], $targetUser['id']);
        if ($result['success']) {
            Response::success([
                'action' => $result['action'],
                'following_count' => $this->followModel->getFollowingCount($currentUser['id']),
                'followers_count' => $this->followModel->getFollowersCount($currentUser['id']),
                'target_following_count' => $this->followModel->getFollowingCount($targetUser['id']),
                'target_followers_count' => $this->followModel->getFollowersCount($targetUser['id'])
            ], $result['message']);
        } else {
            Response::error($result['message'], 400);
        }
    }
}