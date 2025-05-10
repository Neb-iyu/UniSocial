<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Follow.php';

$database = new Database();
$db = $database->getConnection();

$follow = new Follow($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->follower_id) &&
    !empty($data->following_id)
) {
    $follow->follower_id = $data->follower_id;
    $follow->following_id = $data->following_id;

    if($follow->create()) {
        http_response_code(201);
        echo json_encode(array("message" => "Follow relationship was created."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to create follow relationship."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to create follow relationship. Data is incomplete."));
}
?> 