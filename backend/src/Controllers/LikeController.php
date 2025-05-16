<?php

namespace Src\Controllers;

use Src\Models\Like;
use Src\Core\Response;
use Src\Models\Post;
use Src\Models\Comment;


class LikeController extends BaseController
{
    private Like $likeModel;

    public function __construct()
    {
        parent::__construct();
        $this->likeModel = new Like();
    }

    // POST /posts/{uuid}/like
    public function like(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $postModel = new Post();
        $post = $postModel->findByUuid($uuid);
        if (!$post) {
            Response::notFound('Post not found');
            return;
        }
        $result = $this->likeModel->likeToggle($currentUser['id'], $post['id'], null);
        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }

    // POST /comments/{uuid}/like
    public function likeComment(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $commentModel = new Comment();
        $comment = $commentModel->findByUuid($uuid);
        if (!$comment) {
            Response::notFound('Comment not found');
            return;
        }
        $result = $this->likeModel->likeToggle($currentUser['id'], null, $comment['id']);
        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message']);
        }
    }
}
