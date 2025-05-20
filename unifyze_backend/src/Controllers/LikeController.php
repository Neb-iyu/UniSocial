<?php

namespace Src\Controllers;

use Src\Models\Like;
use Src\Core\Response;
use Src\Models\Post;
use Src\Models\Comment;

class LikeController extends BaseController
{
    private Like $likeModel;
    private Post $postModel;
    private Comment $commentModel;

    public function __construct()
    {
        parent::__construct();
        $this->likeModel = new Like();
        $this->postModel = new Post();
        $this->commentModel = new Comment();
    }

    /**
     * Toggle like on a post
     * POST /posts/{uuid}/like
     */
    public function togglePostLike(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;

        $post = $this->postModel->findByUuid($uuid);
        if (!$post) {
            Response::notFound('Post not found');
            return;
        }

        $result = $this->likeModel->likeToggle($currentUser['id'], $post['id'], null);

        if ($result['success']) {
            $updatedPost = $this->postModel->find($post['id']);
            Response::success([
                'message' => $result['message'],
                'likes_count' => $updatedPost['likes_count'] ?? 0,
                'is_liked' => strpos($result['message'], 'Liked') !== false
            ]);
        } else {
            Response::error($result['message'], 400);
        }
    }

    /**
     * Toggle like on a comment
     * POST /comments/{uuid}/like
     */
    public function toggleCommentLike(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;

        $comment = $this->commentModel->findByUuid($uuid);
        if (!$comment) {
            Response::notFound('Comment not found');
            return;
        }

        $result = $this->likeModel->likeToggle($currentUser['id'], null, $comment['id']);

        if ($result['success']) {
            $updatedComment = $this->commentModel->find($comment['id']);
            Response::success([
                'message' => $result['message'],
                'likes_count' => $updatedComment['likes_count'] ?? 0,
                'is_liked' => strpos($result['message'], 'Liked') !== false
            ]);
        } else {
            Response::error($result['message'], 400);
        }
    }

    /**
     * Get likes for a post
     * GET /posts/{uuid}/likes
     */
    public function getPostLikes(string $uuid): void
    {
        $post = $this->postModel->findByUuid($uuid);
        if (!$post) {
            Response::notFound('Post not found');
            return;
        }

        $likes = $this->likeModel->getLikesForPost($post['id']);
        $count = $this->likeModel->countLikesForPost($post['id']);

        foreach ($likes as &$like) {
            $user = $this->userModel->find($like['user_id']);
            $like['username'] = $user['username'] ?? null;
            $like['profile_picture_url'] = $user['profile_picture_url'];
            $postObj = $this->postModel->find($like['post_id']);
            $like['user_uuid'] = $user['public_uuid'] ?? null;
            $like['post_uuid'] = $postObj['public_uuid'] ?? null;
            unset($like['user_id'], $like['post_id'], $like['comment_id'], $like['id']);
        }

        Response::success([
            'likes' => $likes,
            'count' => $count
        ]);
    }

    /**
     * Get like count for a post
     * GET /posts/{uuid}/likes/count
     */
    public function getPostLikeCount(string $uuid): void
    {
        $post = $this->postModel->findByUuid($uuid);
        if (!$post) {
            Response::notFound('Post not found');
            return;
        }

        $count = $this->likeModel->countLikesForPost($post['id']);
        Response::success(['count' => $count]);
    }

    /**
     * Get likes for a comment
     * GET /comments/{uuid}/likes
     */
    public function getCommentLikes(string $uuid): void
    {
        $comment = $this->commentModel->findByUuid($uuid);
        if (!$comment) {
            Response::notFound('Comment not found');
            return;
        }

        $likes = $this->likeModel->getLikesForComment($comment['id']);

        foreach ($likes as &$like) {
            $user = $this->userModel->find($like['user_id']);
            $commentObj = $this->commentModel->find($like['comment_id']);
            $like['user_uuid'] = $user['public_uuid'] ?? null;
            $like['username'] = $user['username'] ?? null;
            $like['profile_picture_url'] = $user['profile_picture_url'];
            $like['comment_uuid'] = $commentObj['public_uuid'] ?? null;
            unset($like['user_id'], $like['comment_id'], $like['post_id'], $like['id']);
        }


        $count = $this->likeModel->countLikesForComment($comment['id']);

        Response::success([
            'likes' => $likes,
            'count' => $count
        ]);
    }

    /**
     * Get like count for a comment
     * GET /comments/{uuid}/likes/count
     */
    public function getCommentLikeCount(string $uuid): void
    {
        $comment = $this->commentModel->findByUuid($uuid);
        if (!$comment) {
            Response::notFound('Comment not found');
            return;
        }

        $count = $this->likeModel->countLikesForComment($comment['id']);
        Response::success(['count' => $count]);
    }
}
