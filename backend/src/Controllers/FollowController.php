<?php

namespace Src\Controllers;

use Src\Models\Follow;
use Src\Core\Response;
use Src\Core\Auth;

class FollowController
{

   
    // POST /users/{id}/follow
    public function follow($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser || $currentUser['id'] == $id) {
            return Response::error('Invalid follow request', 400);
        }
        $followModel = new Follow();
        $result = $followModel->follow($currentUser['id'], $id);
        if ($result) {
            return Response::success(null, 'Followed successfully');
        } else {
            return Response::error('Follow failed', 500);
        }
    }

    // DELETE /users/{id}/follow
    public function unfollow($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser || $currentUser['id'] == $id) {
            return Response::error('Invalid unfollow request', 400);
        }
        $followModel = new Follow();
        $result = $followModel->unfollow($currentUser['id'], $id);
        if ($result) {
            return Response::success(null, 'Unfollowed successfully');
        } else {
            return Response::error('Unfollow failed', 500);
        }
    }

    
}
