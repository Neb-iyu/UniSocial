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

if(isset($_GET['id'])) {
    $user->id = $_GET['id'];
    
    if($user->readOne()) {
        $user_arr = array(
            "id" => $user->id,
            "username" => $user->username,
            "email" => $user->email,
            "created_at" => $user->created_at,
            "updated_at" => $user->updated_at
        );

        http_response_code(200);
        echo json_encode($user_arr);
    } else {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "User not found."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "User ID is required."
    ));
}
?> 