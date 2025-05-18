<?php

namespace Src\Controllers;

use Src\Models\Comment;
use Src\Models\Post;
use Src\Models\Like;
use Src\Core\Response;
use Src\Utilities\Validator;

class CommentController extends BaseController
{
    private Comment $commentModel;

    public function __construct()
    {
        parent::__construct();
        $this->commentModel = new Comment();
    }

    private function filterCommentResponse(array $comment): array
    {
        return [
            'public_uuid' => $comment['public_uuid'] ?? null,
            'content' => $comment['content'] ?? '',
            'user_uuid' => $comment['user_uuid'] ?? null,
            'post_uuid' => $comment['post_uuid'] ?? null,
            'likes_count' => (int)($comment['likes_count'] ?? 0),
            'is_edited' => (bool)($comment['is_edited'] ?? false),
            'is_deleted' => (bool)($comment['is_deleted'] ?? false),
            'post_deleted' => (bool)($comment['post_deleted'] ?? false),
            'created_at' => $comment['created_at'] ?? null,
            'updated_at' => $comment['updated_at'] ?? null
        ];
    }

    // GET /comments
    public function getAllComments(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $comments = $this->commentModel->all();
        foreach ($comments as &$comment) {
            $comment = $this->filterCommentResponse($comment);
        }
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
        if (empty($input['post_uuid'])) {
            $errors['post_uuid'] = 'Post UUID is required.';
        }

        if ($errors) {
            Response::validationError($errors);
            return;
        }

        // Look up post ID from UUID
        $postModel = new Post();
        $post = $postModel->findByUuid($input['post_uuid']);

        if (!$post) {
            Response::notFound('Post not found');
            return;
        }

        // Add the current user's ID and post ID to the input
        $input['user_id'] = $currentUser['id'];
        $input['post_id'] = $post['id'];

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
}
