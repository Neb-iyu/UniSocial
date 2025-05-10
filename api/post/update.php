<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Post.php';

$database = new Database();
$db = $database->getConnection();

$post = new Post($db);

$data = json_decode(file_get_contents("php://input"));

if(
    !empty($data->id) &&
    !empty($data->content)
) {
    $post->id = $data->id;
    
    // Check if post exists
    if(!$post->exists()) {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "Post not found."
        ));
        exit();
    }

    $post->content = $data->content;
    $post->image_url = isset($data->image_url) ? $data->image_url : null;

    if($post->update()) {
        http_response_code(200);
        echo json_encode(array(
            "status" => "success",
            "message" => "Post was updated successfully.",
            "post" => array(
                "id" => $post->id,
                "content" => $post->content,
                "image_url" => $post->image_url
            )
        ));
    } else {
        http_response_code(503);
        echo json_encode(array(
            "status" => "error",
            "message" => "Unable to update post."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Unable to update post. Data is incomplete."
    ));
}
?> 