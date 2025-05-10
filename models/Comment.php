<?php
class Comment {
    private $conn;
    private $table_name = "comments";

    public $id;
    public $user_id;
    public $post_id;
    public $content;
    public $created_at;
    public $updated_at;
    public $username;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    post_id = :post_id,
                    content = :content,
                    created_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        $this->content = htmlspecialchars(strip_tags($this->content));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":post_id", $this->post_id);
        $stmt->bindParam(":content", $this->content);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function readByPost() {
        $query = "SELECT c.*, u.username 
                FROM " . $this->table_name . " c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->post_id);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT c.*, u.username 
                FROM " . $this->table_name . " c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->post_id = $row['post_id'];
            $this->content = $row['content'];
            $this->username = $row['username'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    content = :content,
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    id = :id";

        $stmt = $this->conn->prepare($query);

        $this->content = htmlspecialchars(strip_tags($this->content));

        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function exists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE id = ? LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
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