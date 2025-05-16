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

    private function filterCommentResponse(array $comment): array
    {
        unset($comment['id'], $comment['user_id'], $comment['post_id'], $comment['is_deleted']);
        return $comment;
    }

    // GET /comments
    public function getAllComments(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comments = $this->commentModel->all();
        Response::success($comments, 'Comments fetched successfully');
    }

    // GET /comments/{uuid}
    public function getCommentByUuid(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->findByUuid($uuid);
        if ($comment && !$comment['is_deleted']) {
            $comment = $this->filterCommentResponse($comment);
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
            $comment = $this->filterCommentResponse($comment);
            Response::success($comment, 'Comment created', 201);
        } else {
            Response::error('Comment creation failed', 500);
        }
    }

    // PATCH /comments/{uuid}
    public function updateComment(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->findByUuid($uuid);
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
        $success = $this->commentModel->Update($comment['id'], $input);
        if ($success) {
            $comment = $this->commentModel->findByUuid($uuid);
            $comment = $this->filterCommentResponse($comment);
            Response::success($comment, 'Comment updated');
        } else {
            Response::error('Comment update failed or no valid fields provided', 400);
        }
    }

    // DELETE /comments/{uuid}
    public function deleteComment(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->findByUuid($uuid);
        if (!$comment || $comment['is_deleted']) {
            Response::notFound('Comment not found');
            return;
        }
        if (isset($comment['user_id']) && !$this->requireSelfOrAdmin($currentUser, $comment['user_id'])) return;
        $success = $this->commentModel->Delete($comment['id']);
        if ($success) {
            Response::success(null, 'Comment deleted');
        } else {
            Response::error('Comment deletion failed', 500);
        }
    }

    // GET /comments/{uuid}/likes
    public function getCommentLikes(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->findByUuid($uuid);
        if (!$comment || $comment['is_deleted']) {
            Response::notFound('Comment not found');
            return;
        }
        $likes = $this->likeModel->getLikesForComment($comment['id']);
        Response::success($likes, 'Likes fetched successfully');
    }

    // GET /comments/{uuid}/likes/count
    public function getCommentLikeCount(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comment = $this->commentModel->findByUuid($uuid);
        if (!$comment || $comment['is_deleted']) {
            Response::notFound('Comment not found');
            return;
        }
        $count = $this->likeModel->countLikesForComment($comment['id']);
        Response::success($count, 'Like count fetched successfully');
    }
}
