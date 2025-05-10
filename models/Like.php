<?php
class Like {
    private $conn;
    private $table_name = "likes";

    public $user_id;
    public $post_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    post_id = :post_id,
                    created_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":post_id", $this->post_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE user_id = :user_id AND post_id = :post_id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":post_id", $this->post_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getLikesByPost() {
        $query = "SELECT l.*, u.username 
                FROM " . $this->table_name . " l
                LEFT JOIN users u ON l.user_id = u.id
                WHERE l.post_id = ?
                ORDER BY l.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->post_id);
        $stmt->execute();

        return $stmt;
    }

    public function getLikesByUser() {
        $query = "SELECT l.*, p.title as post_title 
                FROM " . $this->table_name . " l
                LEFT JOIN posts p ON l.post_id = p.id
                WHERE l.user_id = ?
                ORDER BY l.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function exists() {
        $query = "SELECT * FROM " . $this->table_name . " 
                WHERE user_id = ? AND post_id = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->bindParam(2, $this->post_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function userExists() {
        $query = "SELECT id FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function postExists() {
        $query = "SELECT id FROM posts WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->post_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?> 