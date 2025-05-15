<?php

namespace Src\Controllers;

use Src\Models\Role;
use Src\Core\Response;

class RoleController extends BaseController
{
    private Role $roleModel;

    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new Role();
    }

    // POST /roles - Create a new role (superadmin only)
    public function createRole(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $roleName = $input['name'] ?? null;
        $description = $input['description'] ?? null;
        if (!$roleName) {
            Response::validationError(['name' => 'Role name ("name") is required.']);
            return;
        }
        if ($this->roleModel->createRole($roleName, $description)) {
            Response::success(['name' => $roleName], 'Role created successfully.');
        } else {
            Response::error('Failed to create role. The role may already exist.', 500);
        }
    }

    // DELETE /roles/{role} - Delete a role (superadmin only)
    public function deleteRole($roleName): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        if ($this->roleModel->deleteRole($roleName)) {
            Response::success(['name' => $roleName], 'Role deleted successfully.');
        } else {
            Response::error('Failed to delete role. The role may not exist or is protected.', 500);
        }
    }

    // GET /roles - List all roles (admin or superadmin)
    public function getRoles(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        $userRoles = $this->userModel->getUserRoles($currentUser['id']);
        if (!in_array('admin', $userRoles) && !in_array('superadmin', $userRoles)) {
            Response::unauthorized('Only admin or superadmin can view roles.');
            return;
        }
        $allRoles = $this->roleModel->getRoles();
        Response::success($allRoles, 'All available roles fetched successfully.');
    }

    // PATCH /roles/{role} - Update a role's description (superadmin only)
    public function updateRole($roleName): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $description = $input['description'] ?? null;
        if (!$description) {
            Response::validationError(['description' => 'Role description ("description") is required.']);
            return;
        }
        if ($this->roleModel->updateRole($roleName, $description)) {
            Response::success(['name' => $roleName, 'description' => $description], 'Role updated successfully.');
        } else {
            Response::error('Failed to update role. The role may not exist.', 500);
        }
    }

    // POST /roles/assign - Assign a role to a user (superadmin only)
    public function assignRole(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $roleName = $input['role'] ?? null;
        if (!$userId || !$roleName) {
            Response::validationError(['user_id' => 'User ID ("user_id") and role ("role") are required.']);
            return;
        }
        if ($this->userModel->assignRole($userId, $roleName)) {
            Response::success(['user_id' => $userId, 'role' => $roleName], 'Role assigned to user successfully.');
        } else {
            Response::error('Failed to assign role. The user or role may not exist, or the user already has this role.', 500);
        }
    }

    // POST /roles/remove - Remove a role from a user (superadmin only)
    public function removeRole(): void
    {
        $currentUser = $this->requireAuth();
        if (!$currentUser) return;
        if (!$this->requireSuperAdmin($currentUser)) return;
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? null;
        $roleName = $input['role'] ?? null;
        if (!$userId || !$roleName) {
            Response::validationError(['user_id' => 'User ID ("user_id") and role ("role") are required.']);
            return;
        }
        if ($this->userModel->removeRole($userId, $roleName)) {
            Response::success(['user_id' => $userId, 'role' => $roleName], 'Role removed from user successfully.');
        } else {
            Response::error('Failed to remove role. The user or role may not exist, or the user does not have this role.', 500);
        }
    }
}
