<?php

namespace Src\Controllers;

use Src\Models\Mention;
use Src\Core\Response;

class MentionController extends BaseController
{
    private Mention $mentionModel;

    public function __construct()
    {
        parent::__construct();
        $this->mentionModel = new Mention();
    }

    // GET /mentions
    public function getAllMentions(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mentions = $this->mentionModel->allActive();
        Response::success($mentions, 'Mentions fetched successfully');
    }

    // GET /mentions/{id}
    public function getMentionById($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mention = $this->mentionModel->find($id);
        if ($mention && !$mention['is_deleted']) {
            Response::success($mention, 'Mention found');
        } else {
            Response::notFound('Mention not found');
        }
    }

    // POST /mentions
    public function createMention(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $mentionId = $this->mentionModel->create($input);
        if ($mentionId) {
            $mention = $this->mentionModel->find($mentionId);
            Response::success($mention, 'Mention created', 201);
        } else {
            Response::error('Mention creation failed', 500);
        }
    }

    // PATCH /mentions/{id}
    public function updateMention($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mention = $this->mentionModel->find($id);
        if (!$mention || $mention['is_deleted']) {
            Response::notFound('Mention not found');
            return;
        }
        if (isset($mention['user_id']) && !$this->requireSelfOrAdmin($currentUser, $mention['user_id'])) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $success = $this->mentionModel->Update($id, $input);
        if ($success) {
            $mention = $this->mentionModel->find($id);
            Response::success($mention, 'Mention updated');
        } else {
            Response::error('Mention update failed or no valid fields provided', 400);
        }
    }

    // DELETE /mentions/{id}
    public function deleteMention($id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mention = $this->mentionModel->find($id);
        if (!$mention || $mention['is_deleted']) {
            Response::notFound('Mention not found');
            return;
        }
        if (isset($mention['user_id']) && !$this->requireSelfOrAdmin($currentUser, $mention['user_id'])) return;
        $success = $this->mentionModel->Delete($id);
        if ($success) {
            Response::success(null, 'Mention deleted');
        } else {
            Response::error('Mention deletion failed', 500);
        }
    }
}
