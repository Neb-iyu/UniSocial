<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->id) &&
    !empty($data->username) &&
    !empty($data->email)
) {
    $user->id = $data->id;
    
    // Check if user exists
    if(!$user->readOne()) {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "User not found."
        ));
        exit();
    }

    $user->username = $data->username;
    $user->email = $data->email;

    try {
        if($user->update()) {
            http_response_code(200);
            echo json_encode(array(
                "status" => "success",
                "message" => "User was updated successfully.",
                "user" => array(
                    "id" => $user->id,
                    "username" => $user->username,
                    "email" => $user->email
                )
            ));
        }
    } catch(Exception $e) {
        http_response_code(400);
        echo json_encode(array(
            "status" => "error",
            "message" => $e->getMessage()
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to update user. Data is incomplete."
    ));
}
?> 