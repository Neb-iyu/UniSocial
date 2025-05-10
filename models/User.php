<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $email;
    public $password;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        try {
            // First check if username or email already exists
            $check_query = "SELECT username, email FROM " . $this->table_name . " 
                          WHERE username = :username OR email = :email 
                          LIMIT 1";
            
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(":username", $this->username);
            $check_stmt->bindParam(":email", $this->email);
            $check_stmt->execute();

            if($check_stmt->rowCount() > 0) {
                $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
                if($row['username'] === $this->username) {
                    throw new Exception("Username already exists.");
                }
                if($row['email'] === $this->email) {
                    throw new Exception("Email already exists.");
                }
            }

            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        username = :username,
                        email = :email,
                        password = :password,
                        created_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP";

            $stmt = $this->conn->prepare($query);

            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->email = htmlspecialchars(strip_tags($this->email));
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);

            $stmt->bindParam(":username", $this->username);
            $stmt->bindParam(":email", $this->email);
            $stmt->bindParam(":password", $this->password);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(Exception $e) {
            throw $e;
        }
    }

    public function login($email, $password) {
        $query = "SELECT id, username, email, password 
                FROM " . $this->table_name . "
                WHERE email = :email
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function read() {
        $query = "SELECT id, username, email, created_at, updated_at 
                FROM " . $this->table_name . "
                ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function readOne() {
        $query = "SELECT id, username, email, created_at, updated_at 
                FROM " . $this->table_name . "
                WHERE id = ?
                LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    username = :username,
                    email = :email,
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    id = :id";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?> 