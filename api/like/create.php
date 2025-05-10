<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Post.php';

$database = new Database();
$db = $database->getConnection();

$post = new Post($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->user_id) &&
    !empty($data->post_id)
) {
    $post->id = $data->post_id;
    
    if($post->exists()) {
        if($post->like($data->user_id)) {
            http_response_code(201);
            echo json_encode(array("message" => "Post was liked."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to like post."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Post not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to like post. Data is incomplete."));
}
?> 