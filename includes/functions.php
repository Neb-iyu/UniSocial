<?php
require_once 'db.php';


function loginUser(string $email, string $password): bool {
    global $conn;

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
    }

    return false;
}

function registerUser(array $userData): bool {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO users (student_id, email, password, first_name, last_name, department, year_of_study) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $hashedPassword = password_hash($userData['password'], PASSWORD_BCRYPT);
    $stmt->bind_param(
        "ssssssi",
        $userData['student_id'],
        $userData['email'],
        $hashedPassword,
        $userData['first_name'],
        $userData['last_name'],
        $userData['department'],
        $userData['year_of_study']
    );

    return $stmt->execute();
}
