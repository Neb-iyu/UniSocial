<?php

namespace Src\Controllers;

use Src\Models\Follow;
use Src\Core\Response;
use Src\Core\Auth;

class FollowController extends BaseController
{
    private Follow $followModel;

    public function __construct()
    {
        parent::__construct();
        $this->followModel = new Follow();
    }

    // POST /users/{id}/follow
    public function follow($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser || $currentUser['id'] == $id) {
            Response::error('Invalid follow request', 400);
            return;
        }
        $result = $this->followModel->follow($currentUser['id'], $id);
        if ($result) {
            Response::success(null, 'Followed successfully');
        } else {
            Response::error('Follow failed', 500);
        }
    }

    // DELETE /users/{id}/follow
    public function unfollow($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser || $currentUser['id'] == $id) {
            Response::error('Invalid unfollow request', 400);
            return;
        }
        $result = $this->followModel->unfollow($currentUser['id'], $id);
        if ($result) {
            Response::success(null, 'Unfollowed successfully');
        } else {
            Response::error('Unfollow failed', 500);
        }
    }
}
