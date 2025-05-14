<?php

namespace Src\Controllers;

use Src\Models\Mention;
use Src\Core\Response;

class MentionController
{
    // GET /mentions
    public function getAllMentions()
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            \Src\Core\Response::unauthorized('You must be logged in.');
            return;
        }
        $mentionModel = new Mention();
        $mentions = $mentionModel->allActive();
        Response::success($mentions, 'Mentions fetched successfully');
    }

    // GET /mentions/{id}
    public function getMentionById($id)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            \Src\Core\Response::unauthorized('You must be logged in.');
            return;
        }
        $mentionModel = new Mention();
        $mention = $mentionModel->find($id);
        if ($mention && !$mention['is_deleted']) {
            Response::success($mention, 'Mention found');
        } else {
            Response::notFound('Mention not found');
        }
    }

    // POST /mentions
    public function createMention()
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            \Src\Core\Response::unauthorized('You must be logged in.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $mentionModel = new Mention();
        $mentionId = $mentionModel->create($input);
        if ($mentionId) {
            $mention = $mentionModel->find($mentionId);
            Response::success($mention, 'Mention created', 201);
        } else {
            Response::error('Mention creation failed', 500);
        }
    }

    // PATCH /mentions/{id}
    public function updateMention($id)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            \Src\Core\Response::unauthorized('You must be logged in.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $mentionModel = new Mention();
        $success = $mentionModel->Update($id, $input);
        if ($success) {
            $mention = $mentionModel->find($id);
            Response::success($mention, 'Mention updated');
        } else {
            Response::error('Mention update failed or no valid fields provided', 400);
        }
    }

    // DELETE /mentions/{id}
    public function deleteMention($id)
    {
        $auth = new \Src\Core\Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in.');
            return;
        }
        $mentionModel = new Mention();
        $success = $mentionModel->Delete($id);
        if ($success) {
            Response::success(null, 'Mention deleted');
        } else {
            Response::error('Mention deletion failed', 500);
        }
    }
}
