<?php

namespace Src\Controllers;

use Src\Models\Comment;
use Src\Models\Like;
use Src\Core\Response;
use Src\Utilities\Validator;

class CommentController extends BaseController
{
    private Comment $commentModel;
    private Like $likeModel;

    public function __construct()
    {
        parent::__construct();
        $this->commentModel = new Comment();
        $this->likeModel = new Like();
    }

    // GET /comments
    public function getAllComments(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comments = $this->commentModel->all();
        Response::success($comments, 'Comments fetched successfully');
    }

    // GET /comments/{id}
    public function getCommentById($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->find($id);
        if ($comment && !$comment['is_deleted']) {
            Response::success($comment, 'Comment found');
        } else {
            Response::notFound('Comment not found');
        }
    }

    // POST /comments
    public function createComment(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
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
        $commentId = $this->commentModel->create($input);
        if ($commentId) {
            $comment = $this->commentModel->find($commentId);
            Response::success($comment, 'Comment created', 201);
        } else {
            Response::error('Comment creation failed', 500);
        }
    }

    // PATCH /comments/{id}
    public function updateComment($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->find($id);
        if (!$comment || $comment['is_deleted']) {
            Response::notFound('Comment not found');
            return;
        }
        if (isset($comment['user_id']) && !$this->requireSelfOrAdmin($currentUser, $comment['user_id'])) return;
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
        $success = $this->commentModel->Update($id, $input);
        if ($success) {
            $comment = $this->commentModel->find($id);
            Response::success($comment, 'Comment updated');
        } else {
            Response::error('Comment update failed or no valid fields provided', 400);
        }
    }

    // DELETE /comments/{id}
    public function deleteComment($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->find($id);
        if (!$comment || $comment['is_deleted']) {
            Response::notFound('Comment not found');
            return;
        }
        if (isset($comment['user_id']) && !$this->requireSelfOrAdmin($currentUser, $comment['user_id'])) return;
        $success = $this->commentModel->Delete($id);
        if ($success) {
            Response::success(null, 'Comment deleted');
        } else {
            Response::error('Comment deletion failed', 500);
        }
    }

    // GET /comments/{id}/likes
    public function getCommentLikes($commentId): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $likes = $this->likeModel->getLikesForComment($commentId);
        Response::success($likes, 'Likes fetched successfully');
    }

    // GET /comments/{id}/likes/count
    public function getCommentLikeCount($commentId): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $count = $this->likeModel->countLikesForComment($commentId);
        Response::success($count, 'Like count fetched successfully');
    }
}
