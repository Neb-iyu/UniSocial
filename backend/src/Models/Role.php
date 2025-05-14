<?php

namespace Src\Models;

use Src\Core\Model;
use PDO;
use PDOException;

class Role extends Model
{
    protected string $table = 'roles';
    protected string $primaryKey = 'id';

    /**
     * Create a new role (superadmin only)
     * @param string $name
     * @param string|null $description
     * @return bool
     */
    public function createRole(string $name, string $description = null): bool
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO roles (name, description) VALUES (?, ?)");
            return $stmt->execute([$name, $description]);
        } catch (PDOException $e) {
            error_log('Create role failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a role (superadmin only)
     * @param string $name
     * @return bool
     */
    public function deleteRole(string $name): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM roles WHERE name = ?");
            return $stmt->execute([$name]);
        } catch (PDOException $e) {
            error_log('Delete role failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a role's description (superadmin only)
     * @param string $name
     * @param string $description
     * @return bool
     */
    public function updateRole(string $name, string $description): bool
    {
        try {
            $stmt = $this->db->prepare("UPDATE roles SET description = ? WHERE name = ?");
            return $stmt->execute([$description, $name]);
        } catch (PDOException $e) {
            error_log('Update role failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all available roles
     * @return array
     */
    public function getRoles(): array
    {
        try {
            $stmt = $this->db->query("SELECT * FROM roles");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Get roles failed: ' . $e->getMessage());
            return [];
        }
    }
}
