<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

function login($student_id, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_id'] = $user['student_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_unset();
    session_destroy();
}

function registerUser($data) {
    global $pdo;
    
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users 
        (student_id, email, password, first_name, last_name, department, year_of_study) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    return $stmt->execute([
        $data['student_id'],
        $data['email'],
        $hashed_password,
        $data['first_name'],
        $data['last_name'],
        $data['department'],
        $data['year_of_study']
    ]);
}
?>
