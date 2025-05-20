<?php

namespace Src\Controllers;

use Src\Models\Mention;
use Src\Models\User;
use Src\Core\Response;

class MentionController extends BaseController
{
    private Mention $mentionModel;

    public function __construct()
    {
        parent::__construct();
        $this->mentionModel = new Mention();
    }

    private function filterMentionResponse(array $mention): array
    {
        //unset($mention['id'], $mention['mentioned_user_id'], $mention['from_user_id']);
        return $mention;
    }


    // GET /mentions/{uuid}
    public function getMentionByUuid(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mention = $this->mentionModel->findByUuid($uuid);
        if ($mention && !$mention['is_deleted']) {
            $filteredMention = $this->filterMentionResponse($mention);
            Response::success($filteredMention, 'Mention found');
        } else {
            Response::notFound('Mention not found');
        }
    }
    
    
    // GET /users/{uuid}/mentions
    public function getMentionsForUser(string $userUuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
    
        // Find the user by UUID
        $userModel = new User();
        $user = $userModel->findByUuid($userUuid);
    
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
    
        // Fetch mentions for this user
        $mentions = $this->mentionModel->findByMentionedUserId($user['id']);
        $filteredMentions = array_map([$this, 'filterMentionResponse'], $mentions);
    
        Response::success($filteredMentions, 'Mentions fetched successfully');
    }
    

    // PATCH /mentions/{uuid}
    public function updateMention(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mention = $this->mentionModel->findByUuid($uuid);
        if (!$mention || $mention['is_deleted']) {
            Response::notFound('Mention not found');
            return;
        }
        if (isset($mention['user_id']) && !$this->requireSelfOrAdmin($currentUser, $mention['user_id'])) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $success = $this->mentionModel->Update($mention['id'], $input);
        if ($success) {
            $mention = $this->mentionModel->findByUuid($uuid);
            $filteredMention = $this->filterMentionResponse($mention);
            Response::success($filteredMention, 'Mention updated');
        } else {
            Response::error('Mention update failed or no valid fields provided', 400);
        }
    }

    // DELETE /mentions/{uuid}
    public function deleteMention(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $mention = $this->mentionModel->findByUuid($uuid);
        if (!$mention || $mention['is_deleted']) {
            Response::notFound('Mention not found');
            return;
        }
        if (isset($mention['user_id']) && !$this->requireSelfOrAdmin($currentUser, $mention['user_id'])) return;
        $success = $this->mentionModel->Delete($mention['id']);
        if ($success) {
            Response::success(null, 'Mention deleted');
        } else {
            Response::error('Mention deletion failed', 500);
        }
    }
}
