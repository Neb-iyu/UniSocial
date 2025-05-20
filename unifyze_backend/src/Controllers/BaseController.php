<?php

namespace Src\Controllers;

use Src\Core\Auth;
use Src\Core\Response;
use Src\Models\User;

abstract class BaseController
{
    protected Auth $auth;
    protected User $userModel;

    public function __construct()
    {
        $this->auth = new Auth();
        $this->userModel = new User();
    }


    protected function requireAuth(): ?array
    {
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            Response::unauthorized('You must be logged in.');
            return null;
        }
        return $user;
    }


    protected function requireAdmin(array $user): bool
    {
        if (!$this->userModel->is_admin($user['id'])) {
            Response::unauthorized('You must be an admin.');
            return false;
        }
        return true;
    }


    protected function requireSuperAdmin(array $user): bool
    {
        $roles = $this->userModel->getUserRoles($user['id']);
        if (!in_array('superadmin', $roles)) {
            Response::unauthorized('Only superadmin can perform this action.');
            return false;
        }
        return true;
    }


    protected function requireSelfOrAdmin(array $user, int $id): bool
    {
        if ($user['id'] != $id && !$this->userModel->is_admin($user['id'])) {
            Response::unauthorized('You can only perform this action on your own account, unless you are an admin.');
            return false;
        }
        return true;
    }
}
