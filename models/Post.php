<?php
class Post {
    private $conn;
    private $table_name = "posts";

    public $id;
    public $user_id;
    public $username;
    public $content;
    public $created_at;
    public $updated_at;
    public $image_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                SET
                    user_id = :user_id,
                    content = :content,
                    image_url = :image_url,
                    created_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP";

        $stmt = $this->conn->prepare($query);

        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->image_url = $this->image_url ? htmlspecialchars(strip_tags($this->image_url)) : null;

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":image_url", $this->image_url);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read() {
        $query = "SELECT p.*, u.username 
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.user_id = u.id
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT p.*, u.username 
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            $this->username = $row['username'];
            $this->content = $row['content'];
            $this->image_url = $row['image_url'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function readByUser() {
        $query = "SELECT p.*, u.username 
                FROM " . $this->table_name . " p
                LEFT JOIN users u ON p.user_id = u.id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->user_id);
        $stmt->execute();

        return $stmt;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    content = :content,
                    image_url = :image_url,
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    id = :id";

        $stmt = $this->conn->prepare($query);

        $this->content = htmlspecialchars(strip_tags($this->content));
        $this->image_url = $this->image_url ? htmlspecialchars(strip_tags($this->image_url)) : null;

        $stmt->bindParam(":content", $this->content);
        $stmt->bindParam(":image_url", $this->image_url);
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

    public function like($user_id) {
        $query = "INSERT INTO likes (user_id, post_id, created_at)
                VALUES (:user_id, :post_id, CURRENT_TIMESTAMP)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":post_id", $this->id);

        return $stmt->execute();
    }

    public function unlike($user_id) {
        $query = "DELETE FROM likes 
                WHERE user_id = ? AND post_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $this->id);

        return $stmt->execute();
    }

    public function getLikes() {
        $query = "SELECT COUNT(*) as like_count 
                FROM likes 
                WHERE post_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['like_count'];
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
}
?> 