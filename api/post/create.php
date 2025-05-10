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

if(!empty($data->user_id) && !empty($data->content)) {
    $post->user_id = $data->user_id;
    
    // Check if user exists
    if(!$post->userExists()) {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "User not found."
        ));
        exit();
    }

    $post->content = $data->content;
    $post->image_url = isset($data->image_url) ? $data->image_url : null;

    if($post->create()) {
        http_response_code(201);
        echo json_encode(array(
            "message" => "Post was created successfully.",
            "post" => array(
                "user_id" => $post->user_id,
                "content" => $post->content,
                "image_url" => $post->image_url
            )
        ));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to create post."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to create post. Data is incomplete."));
}
?> 