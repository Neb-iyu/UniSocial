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

if(!empty($data->email) && !empty($data->password)) {
    $result = $user->login($data->email, $data->password);
    
    if($result) {
        http_response_code(200);
        echo json_encode(array(
            "message" => "Login successful.",
            "user" => array(
                "id" => $result['id'],
                "username" => $result['username'],
                "email" => $result['email']
            )
        ));
    } else {
        http_response_code(401);
        echo json_encode(array("message" => "Invalid email or password."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}
?> 