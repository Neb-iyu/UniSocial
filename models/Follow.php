<?php
class Follow {
    private $conn;
    private $table_name = "follows";

    public $follower_id;
    public $following_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    follower_id = :follower_id,
                    following_id = :following_id,
                    created_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":follower_id", $this->follower_id);
        $stmt->bindParam(":following_id", $this->following_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE follower_id = ? AND following_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->follower_id);
        $stmt->bindParam(2, $this->following_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getFollowers() {
        $query = "SELECT u.id, u.username 
                FROM " . $this->table_name . " f
                LEFT JOIN users u ON f.follower_id = u.id
                WHERE f.following_id = ?
                ORDER BY f.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->following_id);
        $stmt->execute();

        return $stmt;
    }

    public function getFollowing() {
        $query = "SELECT u.id, u.username 
                FROM " . $this->table_name . " f
                LEFT JOIN users u ON f.following_id = u.id
                WHERE f.follower_id = ?
                ORDER BY f.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->follower_id);
        $stmt->execute();

        return $stmt;
    }

    public function exists() {
        $query = "SELECT follower_id FROM " . $this->table_name . " 
                WHERE follower_id = ? AND following_id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->follower_id);
        $stmt->bindParam(2, $this->following_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function userExists($user_id) {
        $query = "SELECT id FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?> 