<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../../config/database.php';
include_once '../../models/Post.php';

$database = new Database();
$db = $database->getConnection();

$post = new Post($db);

if(isset($_GET['id'])) {
    $post->id = $_GET['id'];
    $post->readOne();

    if($post->id != null) {
        $post_arr = array(
            "id" => $post->id,
            "user_id" => $post->user_id,
            "username" => $post->username,
            "content" => $post->content,
            "image_url" => $post->image_url,
            "created_at" => $post->created_at,
            "updated_at" => $post->updated_at
        );

        http_response_code(200);
        echo json_encode($post_arr);
    } else {
        http_response_code(404);
        echo json_encode(array(
            "status" => "error",
            "message" => "Post not found."
        ));
    }
} else {
    http_response_code(400);
    echo json_encode(array(
        "status" => "error",
        "message" => "Post ID is required."
    ));
}
?> 