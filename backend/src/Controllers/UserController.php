<?php

namespace Src\Controllers;

use Src\Models\User;
use Src\Core\Response;
use Src\Core\Auth;
use Src\Models\Follow;
use Src\Utilities\Validator;
use Src\Utilities\FileUploader;

class UserController
{
    // GET /users
    public function getAllUsers()
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        if (!$currentUser || !$userModel->is_admin($currentUser['id'])) {
            Response::unauthorized('You must be an admin to view all users.');
            return;
        }
        $users = $userModel->allActive();
        foreach ($users as &$user) {
            unset($user['password']);
        }
        Response::success($users, 'Users fetched successfully');
    }

    // GET /users/{id}
    public function getUserById($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to view user details.');
            return;
        }
        $userModel = new User();
        $user = $userModel->find($id);
        if ($user && !$user['is_deleted']) {
            unset($user['password']);
            Response::success($user, 'User found');
        } else {
            Response::notFound('User not found');
        }
    }

    // PATCH /users/{id}
    public function updateUser($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to update your account.');
            return;
        }
        if ($currentUser['id'] != $id) {
            Response::unauthorized('You can only update your own account.');
            return;
        }
        $input = json_decode(file_get_contents('php://input'), true);
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
        if ($errors) {
            Response::validationError($errors);
            return;
        }
        $userModel = new User();
        $success = $userModel->partialUpdate($id, $input);
        if ($success) {
            $user = $userModel->find($id);
            unset($user['password']);
            Response::success($user, 'User updated');
        } else {
            Response::error('User update failed or no valid fields provided', 400);
        }
    }

    // DELETE /users/{id}
    public function deleteUser($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to delete your account.');
            return;
        }
        if ($currentUser['id'] != $id && !$userModel->is_admin($currentUser['id'])) {
            Response::unauthorized('You can only delete your own account, unless you are an admin.');
            return;
        }
        $success = $userModel->softDelete($id);
        if ($success) {
            Response::success(null, 'User deleted');
        } else {
            Response::error('User deletion failed', 500);
        }
    }

    // GET /users/{id}/followers
    public function getFollowers($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to view followers.');
            return;
        }
        $followModel = new Follow();
        $followers = $followModel->getFollowers($id); // returns array of ['follower_id' => ...]
        Response::success($followers, 'Followers fetched successfully');
    }

    // GET /users/{id}/following
    public function getFollowing($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to view following.');
            return;
        }
        $followModel = new Follow();
        $following = $followModel->getFollowing($id); // returns array of ['followed_id' => ...]
        Response::success($following, 'Following fetched successfully');
    }

    // POST /users/{username}/recover
    public function recoverUser($username)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        if (!$currentUser || !$userModel->is_admin($currentUser['id'])) {
            Response::unauthorized('You must be an admin to recover users.');
            return;
        }
        $success = $userModel->recover($username);
        if ($success) {
            $user = $userModel->findByUsername($username);
            if ($user) {
                unset($user['password']);
            }
            Response::success($user, 'User recovered');
        } else {
            Response::error('User recovery failed', 500);
        }
    }

    // POST /users/{id}/profile-picture
    public function uploadProfilePicture($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        if (!$currentUser) {
            Response::unauthorized('You must be logged in to upload a profile picture.');
            return;
        }
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
        $userModel = new User();
        $success = $userModel->update($id, ['profile_picture_url' => $result['path']]);
        if ($success) {
            $user = $userModel->find($id);
            unset($user['password']);
            Response::success($user, 'Profile picture updated');
        } else {
            Response::error('Failed to update profile picture', 500);
        }
    }

    // POST /users/{id}/promote-admin
    public function promoteAdmin($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        $roles = $userModel->getUserRoles($currentUser['id']);
        if (!$currentUser || !in_array('superadmin', $roles)) {
            Response::unauthorized('Only superadmin can promote admins.');
            return;
        }
        if ($userModel->promoteAdmin($id)) {
            Response::success(null, 'User promoted to admin.');
        } else {
            Response::error('Failed to promote user.', 500);
        }
    }

    // POST /users/{id}/demote-admin
    public function demoteAdmin($id)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        $roles = $userModel->getUserRoles($currentUser['id']);
        if (!$currentUser || !in_array('superadmin', $roles)) {
            Response::unauthorized('Only superadmin can demote admins.');
            return;
        }
        if ($userModel->demoteAdmin($id)) {
            Response::success(null, 'Admin demoted to user.');
        } else {
            Response::error('Failed to demote admin.', 500);
        }
    }

    // GET /admins
    public function getAdminList()
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        $roles = $userModel->getUserRoles($currentUser['id']);
        if (!$currentUser || (!in_array('admin', $roles) && !in_array('superadmin', $roles))) {
            Response::unauthorized('Only admin or superadmin can view admin list.');
            return;
        }
        $admins = $userModel->getAdminList();
        foreach ($admins as &$admin) {
            unset($admin['password']);
        }
        Response::success($admins, 'Admin list fetched successfully');
    }

    // GET /users/role/{role}
    public function getUsersByRole($role)
    {
        $auth = new Auth();
        $currentUser = $auth->getCurrentUser();
        $userModel = new User();
        $roles = $userModel->getUserRoles($currentUser['id']);
        if (!$currentUser || (!in_array('admin', $roles) && !in_array('superadmin', $roles))) {
            Response::unauthorized('Only admin or superadmin can view users by role.');
            return;
        }
        $users = $userModel->getUsersByRole($role);
        foreach ($users as &$user) {
            unset($user['password']);
        }
        Response::success($users, 'Users with role ' . $role . ' fetched successfully');
    }
}
