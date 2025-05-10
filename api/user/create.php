<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->username) &&
    !empty($data->email) &&
    !empty($data->password)
) {
    $user->username = $data->username;
    $user->email = $data->email;
    $user->password = $data->password;

    try {
        if($user->create()) {
            http_response_code(201);
            echo json_encode(array(
                "status" => "success",
                "message" => "User was created successfully."
            ));
        } else {
            http_response_code(503);
            echo json_encode(array(
                "status" => "error",
                "message" => "Unable to create user."
            ));
        }
    } catch(Exception $e) {
        if(strpos($e->getMessage(), "already exists") !== false) {
            http_response_code(409);
        } else {
            http_response_code(400);
        }
        echo json_encode(array(
            "status" => "error",
            "message" => $e->getMessage()
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to create user. Data is incomplete."
    ));
}
?> 