<?php

namespace Src\Controllers;

use Src\Core\Response;
use Src\Models\Post;
use Src\Models\Like;
use Src\Utilities\Validator;

class PostController extends BaseController
{
    private Post $postModel;
    private Like $likeModel;

    public function __construct()
    {
        parent::__construct();
        $this->postModel = new Post();
        $this->likeModel = new Like();
    }

    // GET /posts/{id}
    public function getPostById($id): void
    {
        $post = $this->postModel->find($id);
        if ($post && !$post['is_deleted']) {
            Response::success($post, 'Post found');
        } else {
            Response::notFound('Post not found');
        }
    }

    // POST /posts
    public function createPost(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Validator::sanitizeInput($input);
        $input['user_id'] = $currentUser['id'];
        $errors = [];
        if (empty($input['content']) || !is_string($input['content']) || strlen($input['content']) < 1) {
            $errors['content'] = 'Content is required.';
        }
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $postId = $this->postModel->create($input);
        if ($postId) {
            $post = $this->postModel->find($postId);
            Response::success($post, 'Post created', 201);
        } else {
            Response::error('Post creation failed', 500);
        }
    }

    // PATCH /posts/{id}
    public function updatePost($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $post = $this->postModel->find($id);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        if (! $this->requireSelfOrAdmin($currentUser, $post['user_id'])) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Validator::sanitizeInput($input);
        $input['is_edited'] = true;
        $errors = [];
        if (isset($input['content']) && (!is_string($input['content']) || strlen($input['content']) < 1)) {
            $errors['content'] = 'Content must be a non-empty string.';
        }
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $success = $this->postModel->update($id, $input);
        if ($success) {
            $post = $this->postModel->find($id);
            Response::success($post, 'Post updated');
        } else {
            Response::error('Post update failed or no valid fields provided', 400);
        }
    }

    // DELETE /posts/{id}
    public function deletePost($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $post = $this->postModel->find($id);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        if (! $this->requireSelfOrAdmin($currentUser, $post['user_id'])) return;
        // Soft delete: set is_deleted=1, deleted_at=NOW()
        $success = $this->postModel->update($id, [
            'is_deleted' => 1,
            'deleted_at' => date('Y-m-d H:i:s')
        ]);
        if ($success) {
            Response::success(null, 'Post deleted');
        } else {
            Response::error('Post deletion failed', 500);
        }
    }

    // GET /feed
    public function getFeed(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $feed = $this->postModel->getFeed($currentUser['id']);
        Response::success($feed, 'Feed fetched successfully');
    }

    // GET /posts/{id}/likes
    public function getLikes($postId): void
    {
        $likes = $this->likeModel->getLikesForPost($postId);
        Response::success($likes, 'Likes fetched successfully');
    }

    // GET /posts/{id}/likes/count
    public function getLikeCount($postId): void
    {
        $count = $this->likeModel->countLikesForPost($postId);
        Response::success($count, 'Like count fetched successfully');
    }
}
