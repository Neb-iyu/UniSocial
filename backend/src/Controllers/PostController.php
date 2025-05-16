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

    private function filterPostResponse(array $post): array
    {
        unset($post['id'], $post['user_id'], $post['is_deleted']);
        return $post;
    }

    // GET /posts/{uuid}
    public function getPostByUuid(string $uuid): void
    {
        $post = $this->postModel->findByUuid($uuid);
        if ($post && !$post['is_deleted']) {
            $post = $this->filterPostResponse($post);
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
            $post = $this->filterPostResponse($post);
            Response::success($post, 'Post created', 201);
        } else {
            Response::error('Post creation failed', 500);
        }
    }

    // PATCH /posts/{uuid}
    public function updatePost(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $post = $this->postModel->findByUuid($uuid);
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
        $success = $this->postModel->update($post['id'], $input);
        if ($success) {
            $post = $this->postModel->findByUuid($uuid);
            $post = $this->filterPostResponse($post);
            Response::success($post, 'Post updated');
        } else {
            Response::error('Post update failed or no valid fields provided', 400);
        }
    }

    // DELETE /posts/{uuid}
    public function deletePost(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $post = $this->postModel->findByUuid($uuid);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        if (! $this->requireSelfOrAdmin($currentUser, $post['user_id'])) return;
        // Soft delete: set is_deleted=1, deleted_at=NOW()
        $success = $this->postModel->update($post['id'], [
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

    // GET /posts/{uuid}/likes
    public function getLikes(string $uuid): void
    {
        $post = $this->postModel->findByUuid($uuid);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        $likes = $this->likeModel->getLikesForPost($post['id']);
        Response::success($likes, 'Likes fetched successfully');
    }

    // GET /posts/{uuid}/likes/count
    public function getLikeCount(string $uuid): void
    {
        $post = $this->postModel->findByUuid($uuid);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        $count = $this->likeModel->countLikesForPost($post['id']);
        Response::success($count, 'Like count fetched successfully');
    }
}
