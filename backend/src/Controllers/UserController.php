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
            unset($user['password']);
        }
        Response::success($users, 'Users fetched successfully');
    }

    // GET /users/{id}
    public function getUserById(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $user = $this->userModel->find($id);
        if ($user && !$user['is_deleted']) {
            unset($user['password']);
            Response::success($user, 'User found');
        } else {
            Response::notFound('User not found');
        }
    }

    // PATCH /users/{id}
    public function updateUser(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if ($currentUser['id'] != $id) {
            Response::unauthorized('You can only update your own account.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $errors = $this->validateUpdateInput($input);
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $success = $this->userModel->partialUpdate($id, $input);
        if ($success) {
            $user = $this->userModel->find($id);
            unset($user['password']);
            Response::success($user, 'User updated');
        } else {
            Response::error('User update failed or no valid fields provided', 400);
        }
    }

    // DELETE /users/{id}
    public function deleteUser(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSelfOrAdmin($currentUser, $id)) return;
        $success = $this->userModel->softDelete($id);
        if ($success) {
            Response::success(null, 'User deleted');
        } else {
            Response::error('User deletion failed', 500);
        }
    }

    // GET /users/{id}/followers
    public function getFollowers(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $followers = $this->followModel->getFollowers($id); // returns array of ['follower_id' => ...]
        Response::success($followers, 'Followers fetched successfully');
    }

    // GET /users/{id}/following
    public function getFollowing(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $following = $this->followModel->getFollowing($id); // returns array of ['followed_id' => ...]
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
                unset($user['password']);
            }
            Response::success($user, 'User recovered');
        } else {
            Response::error('User recovery failed', 500);
        }
    }

    // POST /users/{id}/profile-picture
    public function uploadProfilePicture(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if ($currentUser['id'] != $id) {
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
        $success = $this->userModel->update($id, ['profile_picture_url' => $result['path']]);
        if ($success) {
            $user = $this->userModel->find($id);
            unset($user['password']);
            Response::success($user, 'Profile picture updated');
        } else {
            Response::error('Failed to update profile picture', 500);
        }
    }

    // POST /users/{id}/promote-admin
    public function promoteAdmin(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        if ($this->userModel->promoteAdmin($id)) {
            Response::success(null, 'User promoted to admin.');
        } else {
            Response::error('Failed to promote user.', 500);
        }
    }

    // POST /users/{id}/demote-admin
    public function demoteAdmin(int $id): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        if ($this->userModel->demoteAdmin($id)) {
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
            unset($admin['password']);
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
            unset($user['password']);
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
}
