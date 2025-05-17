<?php

namespace Src\Controllers;

use Src\Models\User;
use Src\Core\Response;
use Src\Core\Auth;
use Src\Models\Follow;
use Src\Utilities\Validator;
use Src\Utilities\FileUploader;

class UserController extends BaseController
{
    private Follow $followModel;

    public function __construct()
    {
        parent::__construct();
        $this->followModel = new Follow();
    }

    // GET /users
    public function getAllUsers(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireAdmin($currentUser)) return;
        $users = $this->userModel->allActive();
        foreach ($users as &$user) {
            $user = $this->filterUserResponse($user);
        }
        Response::success($users, 'Users fetched successfully');
    }

    // GET /users/{uuid}
    public function getUserByUuid(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->findByUuid($uuid);
        if ($user && !$user['is_deleted']) {
            $user = $this->filterUserResponse($user);
            Response::success($user, 'User found');
        } else {
            Response::notFound('User not found');
        }
    }

    // PATCH /users/{uuid}
    public function updateUser(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        if ($currentUser['public_uuid'] !== $uuid) {
            Response::unauthorized('You can only update your own account.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validateUpdateInput($input);
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $success = $this->userModel->partialUpdate($user['id'], $input);
        if ($success) {
            $user = $this->userModel->findByUuid($uuid);
            $user = $this->filterUserResponse($user);
            Response::success($user, 'User updated');
        } else {
            Response::error('User update failed or no valid fields provided', 400);
        }
    }

    // DELETE /users/{uuid}
    public function deleteUser(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        if ($currentUser['public_uuid'] !== $uuid && !$this->requireSelfOrAdmin($currentUser, $user['id'])) return;
        $success = $this->userModel->softDelete($user['id']);
        if ($success) {
            Response::success(null, 'User deleted');
        } else {
            Response::error('User deletion failed', 500);
        }
    }

    // GET /users/{uuid}/followers
    public function getFollowers(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        $followers = $this->followModel->getFollowers($user['id']);
        foreach ($followers as &$follower) {
            $follower = $this->filterUserResponse($follower);
        }
        Response::success($followers, 'Followers fetched successfully');
    }

    // GET /users/{uuid}/following
    public function getFollowing(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        $following = $this->followModel->getFollowing($user['id']);
        foreach ($following as &$followee) {
            $followee = $this->filterUserResponse($followee);
        }
        Response::success($following, 'Following fetched successfully');
    }

    // POST /users/{username}/recover
    public function recoverUser(string $username): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireAdmin($currentUser)) return;
        $success = $this->userModel->recover($username);
        if ($success) {
            $user = $this->userModel->findByUsername($username);
            if ($user) {
                $user = $this->filterUserResponse($user);
            }
            Response::success($user, 'User recovered');
        } else {
            Response::error('User recovery failed', 500);
        }
    }

    // POST /users/{uuid}/profile-picture
    public function uploadProfilePicture(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        if ($currentUser['public_uuid'] !== $uuid) {
            Response::unauthorized('You can only update your own profile picture.');
            return;
        }
        if (!isset($_FILES['profile_picture'])) {
            Response::validationError(['profile_picture' => 'No file uploaded.']);
            return;
        }
        $result = FileUploader::upload($_FILES['profile_picture'], 'profiles');
        if (!$result['success']) {
            Response::error($result['error'], 400);
            return;
        }
        $success = $this->userModel->update($user['id'], ['profile_picture_url' => $result['path']]);
        if ($success) {
            $user = $this->userModel->findByUuid($uuid);
            $user = $this->filterUserResponse($user);
            Response::success($user, 'Profile picture updated');
        } else {
            Response::error('Failed to update profile picture', 500);
        }
    }

    // POST /users/{uuid}/promote-admin
    public function promoteAdmin(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        if ($this->userModel->promoteAdmin($user['id'])) {
            Response::success(null, 'User promoted to admin.');
        } else {
            Response::error('Failed to promote user.', 500);
        }
    }

    // POST /users/{uuid}/demote-admin
    public function demoteAdmin(string $uuid): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        $user = $this->userModel->findByUuid($uuid);
        if (!$user) {
            Response::notFound('User not found');
            return;
        }
        if ($this->userModel->demoteAdmin($user['id'])) {
            Response::success(null, 'Admin demoted to user.');
        } else {
            Response::error('Failed to demote admin.', 500);
        }
    }

    // GET /admins
    public function getAdminList(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $roles = $this->userModel->getUserRoles($currentUser['id']);
        if (!in_array('admin', $roles) && !in_array('superadmin', $roles)) {
            Response::unauthorized('Only admin or superadmin can view admin list.');
            return;
        }
        $admins = $this->userModel->getAdminList();
        foreach ($admins as &$admin) {
            $admin = $this->filterUserResponse($admin);
        }
        Response::success($admins, 'Admin list fetched successfully');
    }

    // GET /users/role/{role}
    public function getUsersByRole(string $role): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $roles = $this->userModel->getUserRoles($currentUser['id']);
        if (!in_array('admin', $roles) && !in_array('superadmin', $roles)) {
            Response::unauthorized('Only admin or superadmin can view users by role.');
            return;
        }
        $users = $this->userModel->getUsersByRole($role);
        foreach ($users as &$user) {
            $user = $this->filterUserResponse($user);
        }
        Response::success($users, 'Users with role ' . $role . ' fetched successfully');
    }

    // Validation helper
    private function validateUpdateInput(array $input): array
    {
        $input = Validator::sanitizeInput($input);
        $errors = [];
        if (isset($input['email']) && !Validator::email($input['email'])) {
            $errors['email'] = 'Valid email is required';
        }
        if (isset($input['username']) && !Validator::username($input['username'])) {
            $errors['username'] = 'Username must be 5-20 chars, alphanumeric or underscore';
        }
        if (isset($input['password']) && !Validator::password($input['password'])) {
            $errors['password'] = 'Password must be at least 8 characters, include a letter and a number';
        }
        return $errors;
    }

    private function filterUserResponse(array $user): array
    {
        $profilePictureUrl = $this->userModel->getProfilePictureUrl($user['id']);
        
        return [
            'public_uuid' => $user['public_uuid'] ?? null,
            'username' => $user['username'] ?? null,
            'email' => $user['email'] ?? null,
            'fullname' => $user['fullname'] ?? null,
            'bio' => $user['bio'] ?? null,
            'profile_picture_url' => $profilePictureUrl,
            'university_id' => $user['university_id'] ?? null,
            'year_of_study' => isset($user['year_of_study']) ? (int)$user['year_of_study'] : null,
            'gender' => $user['gender'] ?? null,
            'followers_count' => (int)($user['followers_count'] ?? 0),
            'following_count' => (int)($user['following_count'] ?? 0),
            'post_count' => (int)($user['post_count'] ?? 0),
            'created_at' => $user['created_at'] ?? null,
            'is_admin' => !empty($user['is_admin'])
        ];
    }
}
