<?php

namespace Src\Controllers;

use Src\Models\Like;
use Src\Core\Response;

class LikeController extends BaseController
{
    private Like $likeModel;

    public function __construct()
    {
        parent::__construct();
        $this->likeModel = new Like();
    }

    // POST /posts/{id}/like
    public function like($postId): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $liked = $this->likeModel->likeToggle($currentUser['id'], $postId, null);
        if ($liked) {
            Response::success(null, 'Post liked');
        } else {
            Response::success(null, 'Post unliked');
        }
    }

    // POST /comments/{id}/like
    public function likeComment($commentId): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $liked = $this->likeModel->likeToggle($currentUser['id'], null, $commentId);
        if ($liked) {
            Response::success(null, 'Comment liked');
        } else {
            Response::success(null, 'Comment unliked');
        }
    }
}
