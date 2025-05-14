<?php

namespace Src\Controllers;

use Src\Models\Comment;
use Src\Core\Response;
use Src\Utilities\Validator;

class CommentController
{
    // GET /comments
    public function getAllComments()
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $commentModel = new Comment();
        $comments = $commentModel->all();
        Response::success($comments, 'Comments fetched successfully');
    }

    // GET /comments/{id}
    public function getCommentById($id)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $commentModel = new Comment();
        $comment = $commentModel->find($id);
        if ($comment && !$comment['is_deleted']) {
            Response::success($comment, 'Comment found');
        } else {
            Response::notFound('Comment not found');
        }
    }

    // POST /comments
    public function createComment()
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Validator::sanitizeInput($input);
        $errors = [];
        if (empty($input['content']) || !is_string($input['content']) || strlen($input['content']) < 1) {
            $errors['content'] = 'Content is required.';
        }
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $commentModel = new Comment();
        $commentId = $commentModel->create($input);
        if ($commentId) {
            $comment = $commentModel->find($commentId);
            Response::success($comment, 'Comment created', 201);
        } else {
            Response::error('Comment creation failed', 500);
        }
    }

    // PATCH /comments/{id}
    public function updateComment($id)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $input = Validator::sanitizeInput($input);
        $errors = [];
        if (isset($input['content']) && (!is_string($input['content']) || strlen($input['content']) < 1)) {
            $errors['content'] = 'Content must be a non-empty string.';
        }
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $commentModel = new Comment();
        $success = $commentModel->Update($id, $input);
        if ($success) {
            $comment = $commentModel->find($id);
            Response::success($comment, 'Comment updated');
        } else {
            Response::error('Comment update failed or no valid fields provided', 400);
        }
    }

    // DELETE /comments/{id}
    public function deleteComment($id)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $commentModel = new Comment();
        $success = $commentModel->Delete($id);
        if ($success) {
            Response::success(null, 'Comment deleted');
        } else {
            Response::error('Comment deletion failed', 500);
        }
    }

    // GET /comments/{id}/likes
    public function getCommentLikes($commentId)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $likeModel = new \Src\Models\Like();
        $likes = $likeModel->getLikesForComment($commentId);
        Response::success($likes, 'Likes fetched successfully');
    }

    // GET /comments/{id}/likes/count
    public function getCommentLikeCount($commentId)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $likeModel = new \Src\Models\Like();
        $count = $likeModel->countLikesForComment($commentId);
        Response::success($count, 'Like count fetched successfully');
    }
}
