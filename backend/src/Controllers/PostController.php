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
        $user_uuid = $this->userModel->getUuidFromId($post['user_id']);
        $media_urls = json_decode($post['media_urls'] ?? '[]', true) ?: [];
        $media_urls = array_map(['\Src\Utilities\FileUploader', 'getAbsoluteUrl'], $media_urls);
        $response = [
            'public_uuid' => $post['public_uuid'] ?? null,
            'content' => $post['content'] ?? '',
            'media_urls' => $media_urls,
            'visibility' => $post['visibility'] ?? 'public',
            'user_uuid' => $user_uuid,
            'likes_count' => (int)($post['likes_count'] ?? 0),
            'comments_count' => (int)($post['comments_count'] ?? 0),
            'created_at' => $post['created_at'] ?? null,
            'updated_at' => $post['updated_at'] ?? null,
            'is_deleted' => !empty($post['is_deleted']),
            'deleted_at' => $post['deleted_at'] ?? null
        ];

        if (!empty($post['is_deleted']) && isset($post['days_remaining'])) {
            $response['days_remaining'] = (int)$post['days_remaining'];
        }

        return $response;
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

        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $mediaUrls = [];

        // Accepts only one image per post
        $hasImage = false;
        if (!empty($_FILES['image'])) {
            // Single file upload (recommended field: 'image')
            $file = $_FILES['image'];
            $result = \Src\Utilities\FileUploader::upload($file, 'posts');
            if ($result['success']) {
                $mediaUrls[] = \Src\Utilities\FileUploader::getAbsoluteUrl($result['path']);
                $hasImage = true;
            } else {
                Response::error('Image upload failed: ' . $result['error'], 400);
                return;
            }
        } elseif (!empty($_FILES['images'])) {
            // If sent as images[]
            $files = $_FILES['images'];
            if (is_array($files['name']) && count($files['name']) > 1) {
                Response::error('Only one image can be uploaded per post.', 400);
                return;
            }
            $file = [
                'name' => $files['name'][0],
                'type' => $files['type'][0],
                'tmp_name' => $files['tmp_name'][0],
                'error' => $files['error'][0],
                'size' => $files['size'][0]
            ];
            $result = \Src\Utilities\FileUploader::upload($file, 'posts');
            if ($result['success']) {
                $mediaUrls[] = \Src\Utilities\FileUploader::getAbsoluteUrl($result['path']);
                $hasImage = true;
            } else {
                Response::error('Image upload failed: ' . $result['error'], 400);
                return;
            }
        }

        // Validation: at least one of content or image must be present
        if (empty($content) && !$hasImage) {
            Response::validationError(['content' => 'Text content or an image is required.']);
            return;
        }
        $postModel = new \Src\Models\Post();
        $postData = [
            'user_id' => $currentUser['id'],
            'content' => $content,
            'media_urls' => $mediaUrls
        ];

        $postId = $postModel->create($postData);
        if ($postId) {
            $post = $postModel->find($postId);
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

    // GET /posts/trash
    public function getSoftDeletedPosts(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;

        $deletedPosts = $this->postModel->getSoftDeletedPostByUser($currentUser['id']);

        // Filter the response to include only necessary fields
        $filteredPosts = array_map(function ($post) {
            return $this->filterPostResponse($post);
        }, $deletedPosts);

        Response::success($filteredPosts, 'Soft-deleted posts retrieved successfully');
    }

    // PATCH /posts/{uuid}/recover
    public function recoverPost(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;

        $post = $this->postModel->findByUuid($uuid);
        if (!$post) {
            Response::notFound('Post not found');
            return;
        }

        if ($post['is_deleted'] === 0) {
            Response::error('Post is not deleted', 400);
            return;
        }

        if (! $this->requireSelfOrAdmin($currentUser, $post['user_id'])) {
            return;
        }

        $success = $this->postModel->recover($post['id']);

        if ($success) {
            Response::success(null, 'Post recovered successfully');
        } else {
            Response::error('Failed to recover post', 500);
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

        if (! $this->requireSelfOrAdmin($currentUser, $post['user_id'])) {
            return;
        }

        $success = $this->postModel->softDelete($post['id']);

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
}
