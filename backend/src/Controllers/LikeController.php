<?php

namespace Src\Controllers;

use Src\Models\Like;
use Src\Core\Response;
use Src\Core\Auth;

class LikeController
{
    // POST /posts/{id}/like
    public function like($postId)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to like or unlike a post.');
            return;
        }
        $likeModel = new Like();
        $liked = $likeModel->likeToggle($currentUser['id'], $postId, null);
        if ($liked) {
            Response::success(null, 'Post liked');
        } else {
            Response::success(null, 'Post unliked');
        }
    }

    // POST /comments/{id}/like
    public function likeComment($commentId)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to like or unlike a comment.');
            return;
        }
        $likeModel = new Like();
        $liked = $likeModel->likeToggle($currentUser['id'], null, $commentId);
        if ($liked) {
            Response::success(null, 'Comment liked');
        } else {
            Response::success(null, 'Comment unliked');
        }
    }                 
    
}
