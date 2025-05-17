<?php

namespace Src\Models;

use Src\Core\Model;
use PDO;
use PDOException;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'fullname',
        'username',
        'email',
        'password',
        'bio',
        'profile_picture_url',
        'university_id',
        'year_of_study',
        'gender'
    ];


    public function findByEmail(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} 
                 WHERE email = :email 
                 AND is_deleted = 0 
                 LIMIT 1"
            );
            $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("User lookup failed for email {$email}: " . $e->getMessage());
            return null;
        }
    }


    public function softDelete(int $userId): bool
    {
        try {
            $this->db->beginTransaction();

            // Mark user as deleted
            $stmt = $this->db->prepare(
                "UPDATE {$this->table} 
                 SET is_deleted = 1, 
                     username = CONCAT('deleted_', username),
                     email = CONCAT('deleted_', email),
                     updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$userId]);

            // Remove follows
            $this->db->prepare(
                "DELETE FROM follows 
                 WHERE follower_id = ? OR followed_id = ?"
            )->execute([$userId, $userId]);

            // Remove likes
            $this->db->prepare(
                "DELETE FROM likes 
                 WHERE user_id = ?"
            )->execute([$userId]);

            return $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Soft delete failed for user {$userId}: " . $e->getMessage());
            return false;
        }
    }


    public function getProfilePictureUrl(int $userId): string
    {
        $user = $this->find($userId);

        if (!$user) {
            return 'uploads/profiles/default.svg';
        }

        if (empty($user['profile_picture_url'])) {
            return 'uploads/profiles/default.svg';
        }

        $filePath = __DIR__ . '/../../public/' . ltrim($user['profile_picture_url'], '/');
        if (!file_exists($filePath)) {
            return 'uploads/profiles/default.svg';
        }

        return $user['profile_picture_url'];
    }

    public function create(array $data): int
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = $this->hashPassword($data['password']);
            }

            // Sets default profile picture if not provided
            if (empty($data['profile_picture_url'])) {
                $data['profile_picture_url'] = 'uploads/profiles/default.svg';
            }

            $data = array_intersect_key($data, array_flip($this->fillable));
            $userId = parent::create($data);

            // Assign 'user' role by default
            if ($userId) {
                $this->assignRole($userId, 'user');
            }

            return $userId;
        } catch (PDOException $e) {
            error_log('User create failed: ' . $e->getMessage());
            return 0;
        }
    }


    public function update(int $id, array $data): bool
    {
        try {
            if (isset($data['password'])) {
                $data['password'] = $this->hashPassword($data['password']);
            }

            $data = array_intersect_key($data, array_flip($this->fillable));
            $result = parent::update($id, $data);


            return $result;
        } catch (PDOException $e) {
            error_log('User update failed: ' . $e->getMessage());
            return false;
        }
    }


    private function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }


    public function verifyCredentials(string $email, string $password): bool
    {
        try {
            $user = $this->findByEmail($email);
            return $user && password_verify($password, $user['password']);
        } catch (PDOException $e) {
            error_log('Verify credentials failed: ' . $e->getMessage());
            return false;
        }
    }


    public function findByUsername(string $username): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} 
                 WHERE username = :username 
                 AND is_deleted = 0 
                 LIMIT 1"
            );
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("User lookup failed for username {$username}: " . $e->getMessage());
            return null;
        }
    }


    public function partialUpdate(int $id, array $data): bool
    {
        try {
            // Only update fields that are in $fillable and present in $data, but disallow password update
            $updateData = array_intersect_key($data, array_flip($this->fillable));
            unset($updateData['password']); // Prevent password update via partialUpdate
            if (empty($updateData)) {
                return false;
            }
            return parent::update($id, $updateData);
        } catch (PDOException $e) {
            error_log('User partial update failed: ' . $e->getMessage());
            return false;
        }
    }


    public function allActive(): array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_deleted = 0");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Fetch all active users failed: ' . $e->getMessage());
            return [];
        }
    }


    public function recover(string $username): bool
    {
        try {
            $this->db->beginTransaction();
            // Find the user by username = 'deleted_' . $username and is_deleted = 1
            $deletedUsername = 'deleted_' . $username;
            $stmt = $this->db->prepare("SELECT id, email FROM {$this->table} WHERE username = ? AND is_deleted = 1");
            $stmt->execute([$deletedUsername]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                $this->db->rollBack();
                return false;
            }
            // Set username and email to the provided unprefixed username
            $stmt = $this->db->prepare(
                "UPDATE {$this->table}
                 SET is_deleted = 0,
                     fullname = '',
                     username = :username,
                     email = :email,
                     updated_at = NOW()
                 WHERE id = :id"
            );
            $originalEmail = $user['email'];
            if (strpos($originalEmail, 'deleted_') === 0) {
                $originalEmail = substr($originalEmail, 8);
            }
            $stmt->execute([
                ':username' => $username,
                ':email' => $originalEmail,
                ':id' => $user['id']
            ]);
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Recover failed for username {$username}: " . $e->getMessage());
            return false;
        }
    }

    // --- Role Management Methods ---


    public function promoteAdmin(int $userId): bool
    {
        return $this->assignRole($userId, 'admin');
    }

    public function demoteAdmin(int $userId): bool
    {
        return $this->removeRole($userId, 'admin');
    }

    public function getAdminList(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.* FROM users u
                JOIN user_roles ur ON u.id = ur.user_id
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name IN ('admin', 'superadmin') AND u.is_deleted = 0
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Get admin list failed: ' . $e->getMessage());
            return [];
        }
    }

    public function getUserRoles(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT r.name FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log('Get user roles failed: ' . $e->getMessage());
            return [];
        }
    }


    public function is_admin(int $userId): bool
    {
        $roles = $this->getUserRoles($userId);
        return in_array('admin', $roles) || in_array('superadmin', $roles);
    }


    public function assignRole(int $userId, string $roleName): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$roleName]);
            $roleId = $stmt->fetchColumn();
            if (!$roleId) return false;
            $stmt = $this->db->prepare("INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $roleId]);
        } catch (PDOException $e) {
            error_log('Assign role failed: ' . $e->getMessage());
            return false;
        }
    }


    public function removeRole(int $userId, string $roleName): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM roles WHERE name = ?");
            $stmt->execute([$roleName]);
            $roleId = $stmt->fetchColumn();
            if (!$roleId) return false;
            $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_id = ?");
            return $stmt->execute([$userId, $roleId]);
        } catch (PDOException $e) {
            error_log('Remove role failed: ' . $e->getMessage());
            return false;
        }
    }


    public function getUsersByRole(string $roleName): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT u.* FROM users u
                JOIN user_roles ur ON u.id = ur.user_id
                JOIN roles r ON ur.role_id = r.id
                WHERE r.name = ? AND u.is_deleted = 0
            ");
            $stmt->execute([$roleName]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('Get users by role failed: ' . $e->getMessage());
            return [];
        }
    }


    public function findByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE public_uuid = :uuid AND is_deleted = 0 LIMIT 1"
            );
            $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("User lookup failed for uuid {$uuid}: " . $e->getMessage());
            return null;
        }
    }
}
