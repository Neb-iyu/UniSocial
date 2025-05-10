<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$stmt = $user->read();
$num = $stmt->rowCount();

if($num > 0) {
    $users_arr = array();
    $users_arr["records"] = array();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $user_item = array(
            "id" => $id,
            "username" => $username,
            "email" => $email,
            "created_at" => $created_at,
            "updated_at" => $updated_at
        );

        array_push($users_arr["records"], $user_item);
    }

    http_response_code(200);
    echo json_encode($users_arr);
} else {
    http_response_code(200);
    echo json_encode(array(
        "status" => "success",
        "message" => "No users found.",
        "records" => array()
    ));
}
?> 