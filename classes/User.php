<?php
class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function updateProfile($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE users SET 
            first_name = ?, last_name = ?, bio = ?, department = ?, year_of_study = ?
            WHERE id = ?");
        
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['bio'],
            $data['department'],
            $data['year_of_study'],
            $id
        ]);
    }
    
    public function searchUsers($query) {
        $stmt = $this->pdo->prepare("SELECT * FROM users 
            WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR student_id LIKE ?");
        $stmt->execute(["%$query%", "%$query%"]);
        return $stmt->fetchAll();
    }
    
    public function getEnrolledCourses($userId) {
        $stmt = $this->pdo->prepare("SELECT c.* FROM courses c
            JOIN user_courses uc ON c.id = uc.course_id
            WHERE uc.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>
