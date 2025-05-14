<?php

namespace Src\Controllers;

use Src\Core\Response;
use Src\Models\Post;
use Src\Core\Auth;
use Src\Utilities\Validator;
use Src\Models\User;

class PostController
{

    // GET /posts/{id}
    public function getPostById($id)
    {
        $postModel = new Post();
        $post = $postModel->find($id);
        if ($post && !$post['is_deleted']) {
            Response::success($post, 'Post found');
        } else {
            Response::notFound('Post not found');
        }
    }

    // POST /posts
    public function createPost()
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to create a post.');
            return;
        }
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
        $postModel = new Post();
        $postId = $postModel->create($input);
        if ($postId) {
            $post = $postModel->find($postId);
            Response::success($post, 'Post created', 201);
        } else {
            Response::error('Post creation failed', 500);
        }
    }

    // PATCH /posts/{id}
    public function updatePost($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to update a post.');
            return;
        }
        $userModel = new User();
        $postModel = new Post();
        $post = $postModel->find($id);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        if ($post['user_id'] != $currentUser['id'] && !$userModel->is_admin($currentUser['id'])) {
            Response::unauthorized('You can only update your own posts.');
            return;
        }
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
        $success = $postModel->update($id, $input);
        if ($success) {
            $post = $postModel->find($id);
            Response::success($post, 'Post updated');
        } else {
            Response::error('Post update failed or no valid fields provided', 400);
        }
    }

    // DELETE /posts/{id}
    public function deletePost($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to delete a post.');
            return;
        }
        $userModel = new User();
        $postModel = new Post();
        $post = $postModel->find($id);
        if (!$post || $post['is_deleted']) {
            Response::notFound('Post not found');
            return;
        }
        if ($post['user_id'] != $currentUser['id'] && !$userModel->is_admin($currentUser['id'])) {
            Response::unauthorized('You can only delete your own posts.');
            return;
        }
        // Soft delete: set is_deleted=1, deleted_at=NOW()
        $success = $postModel->update($id, [
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
    public function getFeed()
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to view your feed.');
            return;
        }
        $postModel = new Post();
        $feed = $postModel->getFeed($currentUser['id']);
        Response::success($feed, 'Feed fetched successfully');
    }

    // GET /posts/{id}/likes
    public function getLikes($postId)
    {
        $likeModel = new \Src\Models\Like();
        $likes = $likeModel->getLikesForPost($postId);
        Response::success($likes, 'Likes fetched successfully');
    }

    // GET /posts/{id}/likes/count
    public function getLikeCount($postId)
    {
        $likeModel = new \Src\Models\Like();
        $count = $likeModel->countLikesForPost($postId);
        Response::success($count, 'Like count fetched successfully');
    }
}
